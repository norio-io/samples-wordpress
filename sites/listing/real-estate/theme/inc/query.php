<?php
/**
 * 物件の絞り込み・並べ替え・掲載状態による除外
 *
 * 絞り込みの条件は URL のクエリ（area / layout / rent_max / walk_max / sort）で表す。
 * 共有や再読み込みで同じ結果になるよう、条件の状態はすべて URL に置く。
 *
 * @package hinata-realty
 */

/**
 * 1ページあたりの物件数。3列の一覧で行が揃う数とする。
 */
const HINATA_PER_PAGE = 9;

/**
 * 絞り込みのクエリ変数を登録する。
 *
 * @param string[] $vars 公開クエリ変数。
 * @return string[] 追加後の公開クエリ変数。
 */
function hinata_query_vars( $vars ) {
	return array_merge( $vars, array( 'area', 'layout', 'rent_max', 'walk_max', 'sort' ) );
}
add_filter( 'query_vars', 'hinata_query_vars' );

/**
 * 現在の要求の絞り込み条件を取得する。選択肢にない値は無視する。
 *
 * @return array{area: string, layout: string, rent_max: int, walk_max: int, sort: string} 絞り込み条件。
 */
function hinata_get_filters() {
	$area     = sanitize_title( (string) get_query_var( 'area' ) );
	$layout   = sanitize_title( (string) get_query_var( 'layout' ) );
	$rent_max = (int) get_query_var( 'rent_max' );
	$walk_max = (int) get_query_var( 'walk_max' );
	$sort     = sanitize_key( (string) get_query_var( 'sort' ) );

	return array(
		'area'     => $area && term_exists( $area, 'area' ) ? $area : '',
		'layout'   => $layout && term_exists( $layout, 'layout' ) ? $layout : '',
		'rent_max' => in_array( $rent_max, hinata_rent_max_options(), true ) ? $rent_max : 0,
		'walk_max' => in_array( $walk_max, hinata_walk_max_options(), true ) ? $walk_max : 0,
		'sort'     => array_key_exists( $sort, hinata_sort_options() ) ? $sort : 'new',
	);
}

/**
 * 成約済みの物件を除く条件。掲載状態が未設定の物件は募集中とみなす。
 *
 * @return array meta_query の条件。
 */
function hinata_available_clause() {
	return array(
		'relation' => 'OR',
		array(
			'key'     => 'status',
			'value'   => 'contracted',
			'compare' => '!=',
		),
		array(
			'key'     => 'status',
			'compare' => 'NOT EXISTS',
		),
	);
}

/**
 * 絞り込み条件と並べ替えを、クエリの引数へ反映する。
 *
 * @param array $filters     絞り込み条件（hinata_get_filters() の戻り値）。
 * @param bool  $filter_area エリアで絞り込むか。エリア別一覧ではエリアが URL で決まるため false とする。
 * @return array クエリの引数（tax_query / meta_query / orderby）。
 */
function hinata_filter_query_args( $filters, $filter_area = true ) {
	$tax_query  = array();
	$meta_query = array(
		'relation'  => 'AND',
		'available' => hinata_available_clause(),
	);

	if ( $filter_area && $filters['area'] ) {
		$tax_query[] = array(
			'taxonomy' => 'area',
			'field'    => 'slug',
			'terms'    => $filters['area'],
		);
	}
	if ( $filters['layout'] ) {
		$tax_query[] = array(
			'taxonomy' => 'layout',
			'field'    => 'slug',
			'terms'    => $filters['layout'],
		);
	}
	if ( $filters['rent_max'] ) {
		$meta_query[] = array(
			'key'     => 'rent',
			'value'   => $filters['rent_max'],
			'compare' => '<=',
			'type'    => 'NUMERIC',
		);
	}
	if ( $filters['walk_max'] ) {
		$meta_query[] = array(
			'key'     => 'walk',
			'value'   => $filters['walk_max'],
			'compare' => '<=',
			'type'    => 'NUMERIC',
		);
	}

	$orderby = array( 'date' => 'DESC' );
	if ( 'rent' === $filters['sort'] || 'area' === $filters['sort'] ) {
		// 並べ替えに用いる入力欄を名前付きの条件として加える。値が未設定の物件も結果に残すため、
		// 「存在する」と「存在しない」の OR とし、存在する側の条件名で並べる。
		$key          = 'rent' === $filters['sort'] ? 'rent' : 'sqm';
		$meta_query[] = array(
			'relation'     => 'OR',
			'sort_value'   => array(
				'key'     => $key,
				'compare' => 'EXISTS',
				'type'    => 'rent' === $key ? 'NUMERIC' : 'DECIMAL(10,2)',
			),
			'sort_missing' => array(
				'key'     => $key,
				'compare' => 'NOT EXISTS',
			),
		);
		$orderby      = array(
			'sort_value' => 'rent' === $key ? 'ASC' : 'DESC',
			'date'       => 'DESC',
		);
	}

	$args = array(
		'meta_query' => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 物件数は小規模であり、絞り込みに必要なため。
		'orderby'    => $orderby,
	);
	if ( $tax_query ) {
		$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- 絞り込みに必要なため。
	}
	return $args;
}

/**
 * 物件一覧・エリア別一覧のメインクエリに、絞り込みと並べ替えを反映する。
 *
 * @param WP_Query $query クエリ。
 */
function hinata_pre_get_posts( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	$is_list = $query->is_post_type_archive( 'property' ) || $query->is_tax( array( 'area', 'layout' ) );
	if ( ! $is_list ) {
		return;
	}
	$query->set( 'post_type', 'property' );
	$query->set( 'posts_per_page', HINATA_PER_PAGE );
	foreach ( hinata_filter_query_args( hinata_get_filters(), ! $query->is_tax( 'area' ) ) as $key => $value ) {
		if ( 'tax_query' === $key ) {
			// 分類のアーカイブでは、アーカイブ自体の条件に追加する。
			$value = array_merge( (array) $query->get( 'tax_query' ), $value );
		}
		$query->set( $key, $value );
	}
}
add_action( 'pre_get_posts', 'hinata_pre_get_posts' );

/**
 * クエリループの物件から成約済みを除き、「同じエリアの物件」を現在の物件に合わせる。
 *
 * クエリループの query 属性に "hinataRelated": "area" を加えると、表示中の物件と同じエリアの物件を
 * 表示中の物件を除いて並べる。フィルターに渡るのは投稿テンプレートのブロックであるため、
 * クエリループの属性はブロックのコンテキスト（query）から読む。
 *
 * @param array    $query クエリの引数。
 * @param WP_Block $block クエリループのブロック。
 * @return array 変更後のクエリの引数。
 */
function hinata_query_loop_vars( $query, $block ) {
	if ( 'property' !== ( $query['post_type'] ?? '' ) ) {
		return $query;
	}
	$meta_query          = $query['meta_query'] ?? array();
	$meta_query[]        = hinata_available_clause();
	$query['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 成約済みを除くために必要なため。

	if ( 'area' === ( $block->context['query']['hinataRelated'] ?? '' ) ) {
		$post_id = get_queried_object_id();
		$areas   = wp_get_post_terms( $post_id, 'area', array( 'fields' => 'ids' ) );
		if ( is_wp_error( $areas ) || ! $areas ) {
			$query['post__in'] = array( 0 );
			return $query;
		}
		$query['tax_query']    = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- 同じエリアに限るために必要なため。
			array(
				'taxonomy' => 'area',
				'terms'    => $areas,
			),
		);
		$query['post__not_in'] = array( $post_id );
	}
	return $query;
}
add_filter( 'query_loop_block_query_vars', 'hinata_query_loop_vars', 10, 2 );

/**
 * 掲載中の物件数を返す。
 *
 * @return int 掲載中の物件数。
 */
function hinata_count_available() {
	static $count = null;
	if ( null === $count ) {
		$query = new WP_Query(
			array(
				'post_type'      => 'property',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array( hinata_available_clause() ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 成約済みを除くために必要なため。
			)
		);
		$count = (int) $query->found_posts;
	}
	return $count;
}
