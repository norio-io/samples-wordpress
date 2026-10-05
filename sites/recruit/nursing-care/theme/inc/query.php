<?php
/**
 * 求人の絞り込み・並べ替え・募集状況と掲載期限による除外
 *
 * 絞り込みの条件は URL のクエリ（job_type / employment / place / inexperienced / night / sort）で表す。
 * 共有や再読み込みで同じ結果になるよう、条件の状態はすべて URL に置く。
 *
 * 一覧・トップ・職種別の一覧・施設の詳細には、募集中と急募の求人のうち掲載期限を過ぎていないものだけを表示する。
 * 急募の求人は、どの並べ替えでも他の求人より前に置く。
 *
 * @package moegi-recruit
 */

/**
 * 1ページあたりの求人数。3列・2列の一覧で行が揃う数とする。
 */
const MOEGI_PER_PAGE = 12;

/**
 * 絞り込みのクエリ変数を登録する。
 *
 * 勤務施設は施設の投稿タイプのクエリ変数（facility）と重なるため、place とする。
 *
 * @param string[] $vars 公開クエリ変数。
 * @return string[] 追加後の公開クエリ変数。
 */
function moegi_query_vars( $vars ) {
	return array_merge( $vars, array( 'job_type', 'employment', 'place', 'inexperienced', 'night', 'sort' ) );
}
add_filter( 'query_vars', 'moegi_query_vars' );

/**
 * 現在の要求の絞り込み条件を取得する。選択肢にない値は無視する。
 *
 * @return array{job_type: string, employment: string, place: string, inexperienced: bool, night: string, sort: string} 絞り込み条件。
 */
function moegi_get_filters() {
	$job_type   = sanitize_title( (string) get_query_var( 'job_type' ) );
	$employment = sanitize_title( (string) get_query_var( 'employment' ) );
	$place      = sanitize_title( (string) get_query_var( 'place' ) );
	$night      = sanitize_key( (string) get_query_var( 'night' ) );
	$sort       = sanitize_key( (string) get_query_var( 'sort' ) );

	return array(
		'job_type'      => $job_type && term_exists( $job_type, 'job_type' ) ? $job_type : '',
		'employment'    => $employment && term_exists( $employment, 'employment' ) ? $employment : '',
		'place'         => $place && moegi_facility_by_slug( $place ) ? $place : '',
		'inexperienced' => '1' === (string) get_query_var( 'inexperienced' ),
		'night'         => array_key_exists( $night, moegi_night_options() ) ? $night : '',
		'sort'          => array_key_exists( $sort, moegi_sort_options() ) ? $sort : 'new',
	);
}

/**
 * スラッグから公開中の施設を取得する。
 *
 * @param string $slug 施設のスラッグ。
 * @return WP_Post|null 施設。
 */
function moegi_facility_by_slug( $slug ) {
	$post = get_page_by_path( $slug, OBJECT, 'facility' );
	return $post && 'publish' === $post->post_status ? $post : null;
}

/**
 * 募集中（募集中・急募）で、掲載期限を過ぎていない求人に限る条件。
 *
 * 募集状況の条件に status_value の名前を付ける。並べ替え（急募を先に置く）に用いるため。
 * 募集状況・掲載期限が未設定の求人は、募集中・期限なしとみなす。
 *
 * @return array meta_query の条件。
 */
function moegi_open_clause() {
	return array(
		'relation' => 'AND',
		array(
			'relation'     => 'OR',
			'status_value' => array(
				'key'     => 'status',
				'value'   => 'closed',
				'compare' => '!=',
			),
			array(
				'key'     => 'status',
				'compare' => 'NOT EXISTS',
			),
		),
		array(
			'relation' => 'OR',
			array(
				'key'     => 'deadline',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'   => 'deadline',
				'value' => '',
			),
			// Y-m-d の文字列どうしの比較は、日付の前後と一致する。掲載期限の当日は期限内とする。
			array(
				'key'     => 'deadline',
				'value'   => moegi_today(),
				'compare' => '>=',
			),
		),
	);
}

/**
 * 並べ替えの引数。急募（status の値 urgent）を先に置き、その後を指定の順とする。
 *
 * 給与の高い順は、月給の求人を時給の求人より先に置き、それぞれを給与の下限の高い順に並べる。
 * 月給と時給は金額を直接比べられないため、単位ごとにまとめる。
 *
 * @param string $sort 並べ替え（moegi_sort_options() のキー）。
 * @return array{clauses: array, orderby: array} meta_query に追加する条件と、並べ替え。
 */
function moegi_order_args( $sort ) {
	if ( 'wage' !== $sort ) {
		return array(
			'clauses' => array(),
			'orderby' => array(
				'status_value' => 'DESC',
				'date'         => 'DESC',
			),
		);
	}
	// 並べ替えに用いる入力欄を名前付きの条件として加える。値が未設定の求人も結果に残すため、
	// 「存在する」と「存在しない」の OR とし、存在する側の条件名で並べる。
	return array(
		'clauses' => array(
			array(
				'relation'   => 'OR',
				'unit_value' => array(
					'key'     => 'wage_unit',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => 'wage_unit',
					'compare' => 'NOT EXISTS',
				),
			),
			array(
				'relation'   => 'OR',
				'wage_value' => array(
					'key'     => 'wage_min',
					'compare' => 'EXISTS',
					'type'    => 'NUMERIC',
				),
				array(
					'key'     => 'wage_min',
					'compare' => 'NOT EXISTS',
				),
			),
		),
		'orderby' => array(
			'status_value' => 'DESC',
			'unit_value'   => 'DESC', // monthly（月給）を hourly（時給）より先に置く。
			'wage_value'   => 'DESC',
			'date'         => 'DESC',
		),
	);
}

/**
 * 絞り込み条件と並べ替えを、クエリの引数へ反映する。
 *
 * @param array $filters         絞り込み条件（moegi_get_filters() の戻り値）。
 * @param bool  $filter_job_type 職種で絞り込むか。職種別の一覧では職種が URL で決まるため false とする。
 * @return array クエリの引数（tax_query / meta_query / orderby）。
 */
function moegi_filter_query_args( $filters, $filter_job_type = true ) {
	$tax_query  = array();
	$meta_query = array(
		'relation' => 'AND',
		'open'     => moegi_open_clause(),
	);

	if ( $filter_job_type && $filters['job_type'] ) {
		$tax_query[] = array(
			'taxonomy' => 'job_type',
			'field'    => 'slug',
			'terms'    => $filters['job_type'],
		);
	}
	if ( $filters['employment'] ) {
		$tax_query[] = array(
			'taxonomy' => 'employment',
			'field'    => 'slug',
			'terms'    => $filters['employment'],
		);
	}
	if ( $filters['place'] ) {
		$meta_query[] = array(
			'key'   => 'facility',
			'value' => moegi_facility_by_slug( $filters['place'] )->ID,
			'type'  => 'NUMERIC',
		);
	}
	if ( $filters['inexperienced'] ) {
		$meta_query[] = array(
			'key'   => 'inexperienced',
			'value' => '1',
		);
	}
	if ( 'yes' === $filters['night'] ) {
		$meta_query[] = array(
			'key'     => 'night_shifts',
			'value'   => 0,
			'compare' => '>',
			'type'    => 'NUMERIC',
		);
	}
	if ( 'no' === $filters['night'] ) {
		$meta_query[] = array(
			'relation' => 'OR',
			array(
				'key'     => 'night_shifts',
				'value'   => 0,
				'compare' => '<=',
				'type'    => 'NUMERIC',
			),
			array(
				'key'     => 'night_shifts',
				'compare' => 'NOT EXISTS',
			),
		);
	}

	$order      = moegi_order_args( $filters['sort'] );
	$meta_query = array_merge( $meta_query, $order['clauses'] );

	$args = array(
		'meta_query' => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 求人数は小規模であり、絞り込みに必要なため。
		'orderby'    => $order['orderby'],
	);
	if ( $tax_query ) {
		$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- 絞り込みに必要なため。
	}
	return $args;
}

/**
 * 一覧のメインクエリを整える。
 *
 * - 求人一覧・職種別の一覧: 絞り込みと並べ替えを反映し、募集停止・掲載期限切れの求人を除く
 * - 施設一覧・職員の声の一覧: すべてを「順序」の小さい順に並べる
 *
 * @param WP_Query $query クエリ。
 */
function moegi_pre_get_posts( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( $query->is_post_type_archive( array( 'facility', 'voice' ) ) ) {
		$query->set( 'posts_per_page', -1 );
		$query->set(
			'orderby',
			array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			)
		);
		return;
	}
	$is_list = $query->is_post_type_archive( 'job' ) || $query->is_tax( 'job_type' );
	if ( ! $is_list ) {
		return;
	}
	// 職種は職員の声とも共有するため、職種別の一覧でも求人に限る。
	$query->set( 'post_type', 'job' );
	$query->set( 'posts_per_page', MOEGI_PER_PAGE );
	foreach ( moegi_filter_query_args( moegi_get_filters(), ! $query->is_tax( 'job_type' ) ) as $key => $value ) {
		if ( 'tax_query' === $key ) {
			// 分類のアーカイブでは、アーカイブ自体の条件に追加する。
			$value = array_merge( (array) $query->get( 'tax_query' ), $value );
		}
		$query->set( $key, $value );
	}
}
add_action( 'pre_get_posts', 'moegi_pre_get_posts' );

/**
 * クエリループの対象を、テーマの指定に応じて絞る。
 *
 * 求人のクエリループでは、常に募集停止・掲載期限切れの求人を除き、急募を先に置く。
 * クエリループの query 属性に "moegiRelated" を加えると、表示中のページに関係する投稿に限る。
 * フィルターに渡るのは投稿テンプレートのブロックであるため、クエリループの属性はブロックのコンテキスト（query）から読む。
 *
 * - 求人 × "moegiRelated": "facility"   表示中の施設で募集中の求人（求人の「勤務施設」から逆引きする）
 * - 求人 × "moegiRelated": "job_type"   表示中の職員の声と同じ職種の、募集中の求人
 * - 職員の声 × "moegiRelated": "job_type" 表示中の求人と同じ職種の職員の声
 *
 * @param array    $query クエリの引数。
 * @param WP_Block $block クエリループのブロック。
 * @return array 変更後のクエリの引数。
 */
function moegi_query_loop_vars( $query, $block ) {
	$post_type = $query['post_type'] ?? '';
	$related   = $block->context['query']['moegiRelated'] ?? '';
	$post_id   = get_queried_object_id();

	if ( in_array( $post_type, array( 'facility', 'voice' ), true ) ) {
		$query['orderby'] = array(
			'menu_order' => 'ASC',
			'date'       => 'DESC',
		);
	}
	if ( 'voice' === $post_type && 'job_type' === $related ) {
		$query = moegi_same_job_type( $query, $post_id );
	}
	if ( 'job' !== $post_type ) {
		return $query;
	}

	$order        = moegi_order_args( 'new' );
	$meta_query   = $query['meta_query'] ?? array();
	$meta_query[] = moegi_open_clause();
	if ( 'facility' === $related ) {
		$meta_query[] = array(
			'key'   => 'facility',
			'value' => $post_id,
			'type'  => 'NUMERIC',
		);
	}
	if ( 'job_type' === $related ) {
		$query = moegi_same_job_type( $query, $post_id );
	}
	$query['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 募集停止・掲載期限切れの求人を除くために必要なため。
	$query['orderby']    = $order['orderby'];
	return $query;
}
add_filter( 'query_loop_block_query_vars', 'moegi_query_loop_vars', 10, 2 );

/**
 * クエリを、指定の投稿と同じ職種の投稿に限る。指定の投稿自体は除く。
 *
 * @param array $query   クエリの引数。
 * @param int   $post_id 基準の投稿 ID。
 * @return array 変更後のクエリの引数。
 */
function moegi_same_job_type( $query, $post_id ) {
	$types = wp_get_post_terms( $post_id, 'job_type', array( 'fields' => 'ids' ) );
	if ( is_wp_error( $types ) || ! $types ) {
		$query['post__in'] = array( 0 );
		return $query;
	}
	$query['tax_query']    = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- 同じ職種に限るために必要なため。
		array(
			'taxonomy' => 'job_type',
			'terms'    => $types,
		),
	);
	$query['post__not_in'] = array( $post_id );
	return $query;
}

/**
 * 募集中の求人の件数を返す。
 *
 * @param array $args 条件。job_type（職種の分類の ID）、facility（施設の投稿 ID）を指定できる。
 * @return int 求人数。
 */
function moegi_count_open_jobs( $args = array() ) {
	$meta_query = array( moegi_open_clause() );
	if ( ! empty( $args['facility'] ) ) {
		$meta_query[] = array(
			'key'   => 'facility',
			'value' => (int) $args['facility'],
			'type'  => 'NUMERIC',
		);
	}
	$query = array(
		'post_type'      => 'job',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_query'     => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 募集中の求人に限るために必要なため。
	);
	if ( ! empty( $args['job_type'] ) ) {
		$query['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- 職種ごとの件数に必要なため。
			array(
				'taxonomy' => 'job_type',
				'terms'    => (int) $args['job_type'],
			),
		);
	}
	return (int) ( new WP_Query( $query ) )->found_posts;
}

/**
 * 募集中の求人を取得する（応募フォームの選択肢）。急募を先に、新しい順に並べる。
 *
 * @return WP_Post[] 求人。
 */
function moegi_open_jobs() {
	$order = moegi_order_args( 'new' );
	return get_posts(
		array(
			'post_type'      => 'job',
			'posts_per_page' => -1,
			'meta_query'     => array( moegi_open_clause() ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 募集中の求人に限るために必要なため。
			'orderby'        => $order['orderby'],
		)
	);
}
