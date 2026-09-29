<?php
/**
 * コースの絞り込み・並べ替え・募集状況による除外
 *
 * 絞り込みの条件は URL のクエリ（target / purpose / format / fee_max / sort）で表す。
 * 共有や再読み込みで同じ結果になるよう、条件の状態はすべて URL に置く。
 *
 * @package hibiki-english
 */

/**
 * 1ページあたりのコース数。3列の一覧で行が揃う数とする。
 */
const HIBIKI_PER_PAGE = 12;

/**
 * 絞り込みのクエリ変数を登録する。
 *
 * @param string[] $vars 公開クエリ変数。
 * @return string[] 追加後の公開クエリ変数。
 */
function hibiki_query_vars( $vars ) {
	return array_merge( $vars, array( 'target', 'purpose', 'format', 'fee_max', 'sort' ) );
}
add_filter( 'query_vars', 'hibiki_query_vars' );

/**
 * 現在の要求の絞り込み条件を取得する。選択肢にない値は無視する。
 *
 * @return array{target: string, purpose: string, format: string, fee_max: int, sort: string} 絞り込み条件。
 */
function hibiki_get_filters() {
	$target  = sanitize_title( (string) get_query_var( 'target' ) );
	$purpose = sanitize_title( (string) get_query_var( 'purpose' ) );
	$format  = sanitize_key( (string) get_query_var( 'format' ) );
	$fee_max = (int) get_query_var( 'fee_max' );
	$sort    = sanitize_key( (string) get_query_var( 'sort' ) );

	return array(
		'target'  => $target && term_exists( $target, 'target' ) ? $target : '',
		'purpose' => $purpose && term_exists( $purpose, 'purpose' ) ? $purpose : '',
		'format'  => array_key_exists( $format, hibiki_format_options() ) ? $format : '',
		'fee_max' => in_array( $fee_max, hibiki_fee_max_options(), true ) ? $fee_max : 0,
		'sort'    => array_key_exists( $sort, hibiki_sort_options() ) ? $sort : 'recommended',
	);
}

/**
 * 開講準備中のコースを除く条件。募集状況が未設定のコースは受付中とみなす。
 *
 * @return array meta_query の条件。
 */
function hibiki_listed_clause() {
	return array(
		'relation' => 'OR',
		array(
			'key'     => 'status',
			'value'   => 'preparing',
			'compare' => '!=',
		),
		array(
			'key'     => 'status',
			'compare' => 'NOT EXISTS',
		),
	);
}

/**
 * 受付中（受付中・残りわずか）のコースに限る条件。
 *
 * @return array meta_query の条件。
 */
function hibiki_open_clause() {
	return array(
		'relation' => 'OR',
		array(
			'key'     => 'status',
			'value'   => array( 'open', 'few' ),
			'compare' => 'IN',
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
 * @param array $filters       絞り込み条件（hibiki_get_filters() の戻り値）。
 * @param bool  $filter_target 対象で絞り込むか。対象別の一覧では対象が URL で決まるため false とする。
 * @return array クエリの引数（tax_query / meta_query / orderby）。
 */
function hibiki_filter_query_args( $filters, $filter_target = true ) {
	$tax_query  = array();
	$meta_query = array(
		'relation' => 'AND',
		'listed'   => hibiki_listed_clause(),
	);

	if ( $filter_target && $filters['target'] ) {
		$tax_query[] = array(
			'taxonomy' => 'target',
			'field'    => 'slug',
			'terms'    => $filters['target'],
		);
	}
	if ( $filters['purpose'] ) {
		$tax_query[] = array(
			'taxonomy' => 'purpose',
			'field'    => 'slug',
			'terms'    => $filters['purpose'],
		);
	}
	if ( $filters['format'] ) {
		$meta_query[] = array(
			'key'   => 'format',
			'value' => $filters['format'],
		);
	}
	if ( $filters['fee_max'] ) {
		$meta_query[] = array(
			'key'     => 'fee',
			'value'   => $filters['fee_max'],
			'compare' => '<=',
			'type'    => 'NUMERIC',
		);
	}

	// おすすめ順は「順序」（menu_order）の小さい順とする。同じ順序の場合は新しい順とする。
	$orderby = array(
		'menu_order' => 'ASC',
		'date'       => 'DESC',
	);
	if ( 'fee' === $filters['sort'] ) {
		// 並べ替えに用いる入力欄を名前付きの条件として加える。値が未設定のコースも結果に残すため、
		// 「存在する」と「存在しない」の OR とし、存在する側の条件名で並べる。
		$meta_query[] = array(
			'relation'    => 'OR',
			'sort_fee'    => array(
				'key'     => 'fee',
				'compare' => 'EXISTS',
				'type'    => 'NUMERIC',
			),
			'sort_no_fee' => array(
				'key'     => 'fee',
				'compare' => 'NOT EXISTS',
			),
		);
		$orderby      = array(
			'sort_fee'   => 'ASC',
			'menu_order' => 'ASC',
		);
	}

	$args = array(
		'meta_query' => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- コース数は小規模であり、絞り込みに必要なため。
		'orderby'    => $orderby,
	);
	if ( $tax_query ) {
		$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- 絞り込みに必要なため。
	}
	return $args;
}

/**
 * コース一覧・対象別一覧・講師一覧のメインクエリに、絞り込みと並べ替えを反映する。
 *
 * @param WP_Query $query クエリ。
 */
function hibiki_pre_get_posts( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( $query->is_post_type_archive( 'instructor' ) ) {
		$query->set( 'posts_per_page', -1 );
		$query->set(
			'orderby',
			array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			)
		);
		return;
	}
	$is_list = $query->is_post_type_archive( 'course' ) || $query->is_tax( 'target' );
	if ( ! $is_list ) {
		return;
	}
	$query->set( 'post_type', 'course' );
	$query->set( 'posts_per_page', HIBIKI_PER_PAGE );
	foreach ( hibiki_filter_query_args( hibiki_get_filters(), ! $query->is_tax( 'target' ) ) as $key => $value ) {
		if ( 'tax_query' === $key ) {
			// 分類のアーカイブでは、アーカイブ自体の条件に追加する。
			$value = array_merge( (array) $query->get( 'tax_query' ), $value );
		}
		$query->set( $key, $value );
	}
}
add_action( 'pre_get_posts', 'hibiki_pre_get_posts' );

/**
 * クエリループのコースから開講準備中を除き、テーマの指定に応じて対象を絞る。
 *
 * クエリループの query 属性に次の値を加えると、表示するコースを絞る。フィルターに渡るのは投稿テンプレートの
 * ブロックであるため、クエリループの属性はブロックのコンテキスト（query）から読む。
 *
 * - "hibikiOpen": true              受付中・残りわずかのコースに限る（トップの「受付中のコース」）
 * - "hibikiRelated": "target"       表示中のコースと同じ対象のコースを、表示中のコースを除いて並べる
 * - "hibikiRelated": "instructor"   表示中の講師が担当するコースを並べる（コースの「担当講師」から逆引きする）
 *
 * いずれも並びはおすすめ順（順序の小さい順）とする。
 *
 * @param array    $query クエリの引数。
 * @param WP_Block $block クエリループのブロック。
 * @return array 変更後のクエリの引数。
 */
function hibiki_query_loop_vars( $query, $block ) {
	$post_type = $query['post_type'] ?? '';
	$context   = $block->context['query'] ?? array();

	if ( 'instructor' === $post_type ) {
		$query['orderby'] = array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		);
		return $query;
	}
	if ( 'course' !== $post_type ) {
		return $query;
	}

	$meta_query   = $query['meta_query'] ?? array();
	$meta_query[] = empty( $context['hibikiOpen'] ) ? hibiki_listed_clause() : hibiki_open_clause();

	$related = $context['hibikiRelated'] ?? '';
	$post_id = get_queried_object_id();
	if ( 'target' === $related ) {
		$targets = wp_get_post_terms( $post_id, 'target', array( 'fields' => 'ids' ) );
		if ( is_wp_error( $targets ) || ! $targets ) {
			$query['post__in'] = array( 0 );
			return $query;
		}
		$query['tax_query']    = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- 同じ対象に限るために必要なため。
			array(
				'taxonomy' => 'target',
				'terms'    => $targets,
			),
		);
		$query['post__not_in'] = array( $post_id );
	}
	if ( 'instructor' === $related ) {
		$meta_query[] = array(
			'key'   => 'instructors',
			'value' => $post_id,
			'type'  => 'NUMERIC',
		);
	}

	$query['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 開講準備中を除くために必要なため。
	$query['orderby']    = array(
		'menu_order' => 'ASC',
		'date'       => 'DESC',
	);
	return $query;
}
add_filter( 'query_loop_block_query_vars', 'hibiki_query_loop_vars', 10, 2 );

/**
 * 条件に合うコースの件数を返す。開講準備中は数えない。
 *
 * @param int $target_id 対象の分類の ID。0 の場合はすべての対象。
 * @return int コース数。
 */
function hibiki_count_courses( $target_id = 0 ) {
	$args = array(
		'post_type'      => 'course',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_query'     => array( hibiki_listed_clause() ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 開講準備中を除くために必要なため。
	);
	if ( $target_id ) {
		$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- 対象ごとの件数に必要なため。
			array(
				'taxonomy' => 'target',
				'terms'    => $target_id,
			),
		);
	}
	return (int) ( new WP_Query( $args ) )->found_posts;
}
