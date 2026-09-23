<?php
/**
 * 動的ブロック
 *
 * 物件の値の組み合わせで表示が決まる部分（検索フォーム、件数と並べ替え、物件概要表、設備、問い合わせ導線など）を
 * サーバー側で描画するブロックとして登録する。エディターでは assets/js/editor.js が同じブロックを登録し、
 * サーバー側の描画結果を表示する。
 *
 * @package hinata-realty
 */

/**
 * テーマのブロックの定義。
 *
 * @return array<string, array> ブロック名と登録の引数。
 */
function hinata_block_definitions() {
	return array(
		'hinata/search'             => array(
			'title'           => __( '物件検索', 'hinata-realty' ),
			'attributes'      => array(
				'variant' => array(
					'type'    => 'string',
					'default' => 'panel',
				),
			),
			'render_callback' => 'hinata_render_search',
		),
		'hinata/result-bar'         => array(
			'title'           => __( '件数・条件・並べ替え', 'hinata-realty' ),
			'render_callback' => 'hinata_render_result_bar',
		),
		'hinata/property-summary'   => array(
			'title'           => __( '物件の主要項目', 'hinata-realty' ),
			'uses_context'    => array( 'postId' ),
			'render_callback' => 'hinata_render_property_summary',
		),
		'hinata/property-spec'      => array(
			'title'           => __( '物件概要表', 'hinata-realty' ),
			'uses_context'    => array( 'postId' ),
			'render_callback' => 'hinata_render_property_spec',
		),
		'hinata/property-equipment' => array(
			'title'           => __( '設備', 'hinata-realty' ),
			'uses_context'    => array( 'postId' ),
			'render_callback' => 'hinata_render_property_equipment',
		),
		'hinata/property-cta'       => array(
			'title'           => __( '問い合わせ導線', 'hinata-realty' ),
			'uses_context'    => array( 'postId' ),
			'render_callback' => 'hinata_render_property_cta',
		),
		'hinata/area-list'          => array(
			'title'           => __( 'エリアから探す', 'hinata-realty' ),
			'render_callback' => 'hinata_render_area_list',
		),
		'hinata/store-info'         => array(
			'title'           => __( '店舗情報', 'hinata-realty' ),
			'render_callback' => 'hinata_render_store_info',
		),
		'hinata/contact-form'       => array(
			'title'           => __( '問い合わせフォーム（表示のみ）', 'hinata-realty' ),
			'render_callback' => 'hinata_render_contact_form',
		),
	);
}

/**
 * ブロックを登録する。
 */
function hinata_register_blocks() {
	foreach ( hinata_block_definitions() as $name => $args ) {
		register_block_type(
			$name,
			array_merge(
				array(
					'api_version' => 3,
					'category'    => 'theme',
					'supports'    => array( 'html' => false ),
				),
				$args
			)
		);
	}
}
add_action( 'init', 'hinata_register_blocks' );

/**
 * 問い合わせ対象の物件を受け取るクエリ変数を登録する。
 *
 * @param string[] $vars 公開クエリ変数。
 * @return string[] 追加後の公開クエリ変数。
 */
function hinata_inquiry_query_var( $vars ) {
	$vars[] = 'inquiry';
	return $vars;
}
add_filter( 'query_vars', 'hinata_inquiry_query_var' );

/**
 * テーマ同梱の SVG アイコンを返す。装飾として扱い、支援技術からは隠す。
 *
 * @param string $name アイコン名（assets/icons/<name>.svg）。
 * @return string SVG。
 */
function hinata_icon( $name ) {
	$path = get_theme_file_path( 'assets/icons/' . sanitize_file_name( $name ) . '.svg' );
	if ( ! is_readable( $path ) ) {
		return '';
	}
	// テーマに同梱したファイルのみを読むため、リモートの取得に用いる関数は不要。
	$svg = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	return str_replace( '<svg ', '<svg class="icon" aria-hidden="true" focusable="false" ', trim( $svg ) );
}

/**
 * 描画中の物件の ID を返す。
 *
 * @param WP_Block|null $block ブロック。
 * @return int 物件の投稿 ID。物件でない場合は 0。
 */
function hinata_current_property_id( $block ) {
	$post_id = (int) ( $block->context['postId'] ?? get_the_ID() );
	return $post_id && 'property' === get_post_type( $post_id ) ? $post_id : 0;
}

/**
 * エディターで対象の物件がない場合の表示。
 *
 * @param string $label ブロックの名前。
 * @return string HTML。
 */
function hinata_block_placeholder( $label ) {
	return sprintf( '<div %s><p>%s</p></div>', get_block_wrapper_attributes( array( 'class' => 'hinata-placeholder' ) ), esc_html( $label ) );
}

/**
 * 選択肢の option 要素を返す。
 *
 * @param array  $options  値と表示名。
 * @param string $selected 選択中の値。
 * @return string HTML。
 */
function hinata_options_html( $options, $selected ) {
	$html = '';
	foreach ( $options as $value => $label ) {
		$html .= sprintf( '<option value="%s"%s>%s</option>', esc_attr( $value ), selected( (string) $value, (string) $selected, false ), esc_html( $label ) );
	}
	return $html;
}

/**
 * 物件検索のフォーム。
 *
 * variant=panel はトップの検索パネル（エリア・間取り・賃料上限と掲載件数）、
 * variant=sidebar は物件一覧の絞り込み（駅徒歩を加え、幅の狭い画面では折りたたむ）とする。
 *
 * @param array $attributes 属性。
 * @return string HTML。
 */
function hinata_render_search( $attributes ) {
	$variant = 'sidebar' === ( $attributes['variant'] ?? '' ) ? 'sidebar' : 'panel';
	$filters = hinata_get_filters();
	$uid     = wp_unique_id( 'hinata-search-' );

	$area_selected = $filters['area'];
	$current_term  = get_queried_object();
	if ( ! $area_selected && $current_term instanceof WP_Term && 'area' === $current_term->taxonomy ) {
		$area_selected = $current_term->slug;
	}

	$terms = static function ( $taxonomy ) {
		$list = array( '' => __( '指定なし', 'hinata-realty' ) );
		foreach ( get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'orderby'    => 'term_id',
			)
		) as $term ) {
			$list[ $term->slug ] = $term->name;
		}
		return $list;
	};

	$rent = array( '' => __( '指定なし', 'hinata-realty' ) );
	foreach ( hinata_rent_max_options() as $yen ) {
		$rent[ $yen ] = sprintf( '%s以下', hinata_format_man_yen( $yen ) );
	}
	$walk = array( '' => __( '指定なし', 'hinata-realty' ) );
	foreach ( hinata_walk_max_options() as $min ) {
		$walk[ $min ] = sprintf( '%d分以内', $min );
	}

	$field = static function ( $name, $label, $options, $selected ) use ( $uid ) {
		$id = $uid . '-' . $name;
		return sprintf(
			'<div class="hinata-search__field"><label for="%1$s">%2$s</label><select id="%1$s" name="%3$s">%4$s</select></div>',
			esc_attr( $id ),
			esc_html( $label ),
			esc_attr( $name ),
			hinata_options_html( $options, $selected )
		);
	};

	$fields  = $field( 'area', __( 'エリア', 'hinata-realty' ), $terms( 'area' ), $area_selected );
	$fields .= $field( 'layout', __( '間取り', 'hinata-realty' ), $terms( 'layout' ), $filters['layout'] );
	$fields .= $field( 'rent_max', __( '賃料の上限', 'hinata-realty' ), $rent, $filters['rent_max'] ? $filters['rent_max'] : '' );

	if ( 'sidebar' === $variant ) {
		$fields .= $field( 'walk_max', __( '駅徒歩', 'hinata-realty' ), $walk, $filters['walk_max'] ? $filters['walk_max'] : '' );
		if ( 'new' !== $filters['sort'] ) {
			$fields .= sprintf( '<input type="hidden" name="sort" value="%s">', esc_attr( $filters['sort'] ) );
		}
	}

	$form = sprintf(
		'<form class="hinata-search__form" action="%1$s" method="get" role="search" aria-label="%2$s">%3$s<button type="submit" class="hinata-search__submit">%4$s</button></form>',
		esc_url( get_post_type_archive_link( 'property' ) ),
		esc_attr__( '物件検索', 'hinata-realty' ),
		$fields,
		esc_html__( 'この条件で探す', 'hinata-realty' )
	);

	if ( 'panel' === $variant ) {
		$count = sprintf(
			'<p class="hinata-search__count">%1$s <span class="num">%2$s</span>%3$s</p>',
			esc_html__( '掲載中の物件', 'hinata-realty' ),
			esc_html( number_format( hinata_count_available() ) ),
			esc_html__( '件', 'hinata-realty' )
		);
		return sprintf( '<div %s>%s%s</div>', get_block_wrapper_attributes( array( 'class' => 'is-panel' ) ), $form, $count );
	}

	// 幅の狭い画面では assets/js/front.js が折りたたむ。スクリプトが動かない場合は開いたまま表示する。
	return sprintf(
		'<div %1$s><details class="hinata-search__details" open><summary>%2$s</summary>%3$s</details></div>',
		get_block_wrapper_attributes( array( 'class' => 'is-sidebar' ) ),
		esc_html__( '条件で絞り込む', 'hinata-realty' ),
		$form
	);
}

/**
 * 件数・選択中の条件（解除できるチップ）・並べ替え。
 *
 * @return string HTML。
 */
function hinata_render_result_bar() {
	global $wp_query;
	$filters = hinata_get_filters();
	$term    = get_queried_object();
	$on_area = $term instanceof WP_Term && 'area' === $term->taxonomy;
	$base    = $on_area ? get_term_link( $term ) : get_post_type_archive_link( 'property' );
	if ( $on_area ) {
		$filters['area'] = '';
	}

	$url = static function ( $changes ) use ( $filters, $base ) {
		$next = array_merge( $filters, $changes );
		$args = array_filter(
			array(
				'area'     => $next['area'],
				'layout'   => $next['layout'],
				'rent_max' => $next['rent_max'],
				'walk_max' => $next['walk_max'],
				'sort'     => 'new' === $next['sort'] ? '' : $next['sort'],
			)
		);
		return esc_url( add_query_arg( $args, $base ) );
	};

	$chips = array();
	if ( $filters['area'] ) {
		$t       = get_term_by( 'slug', $filters['area'], 'area' );
		$chips[] = array( __( 'エリア', 'hinata-realty' ), $t ? $t->name : $filters['area'], array( 'area' => '' ) );
	}
	if ( $filters['layout'] ) {
		$t       = get_term_by( 'slug', $filters['layout'], 'layout' );
		$chips[] = array( __( '間取り', 'hinata-realty' ), $t ? $t->name : $filters['layout'], array( 'layout' => '' ) );
	}
	if ( $filters['rent_max'] ) {
		$chips[] = array( __( '賃料', 'hinata-realty' ), hinata_format_man_yen( $filters['rent_max'] ) . '以下', array( 'rent_max' => 0 ) );
	}
	if ( $filters['walk_max'] ) {
		$chips[] = array( __( '駅徒歩', 'hinata-realty' ), sprintf( '%d分以内', $filters['walk_max'] ), array( 'walk_max' => 0 ) );
	}

	$chips_html = '';
	if ( $chips ) {
		$items = '';
		foreach ( $chips as $chip ) {
			list( $label, $value, $changes ) = $chip;
			$items                          .= sprintf(
				'<li><a class="chip" href="%1$s" aria-label="%2$s"><span class="chip__label">%3$s</span>%4$s<span class="chip__x" aria-hidden="true">×</span></a></li>',
				$url( $changes ),
				/* translators: 1: 条件の種類 2: 条件の値 */
				esc_attr( sprintf( __( '条件を解除: %1$s %2$s', 'hinata-realty' ), $label, $value ) ),
				esc_html( $label ),
				esc_html( $value )
			);
		}
		$items     .= sprintf(
			'<li><a class="chip-clear" href="%1$s">%2$s</a></li>',
			$url(
				array(
					'area'     => '',
					'layout'   => '',
					'rent_max' => 0,
					'walk_max' => 0,
				)
			),
			esc_html__( 'すべて解除', 'hinata-realty' )
		);
		$chips_html = sprintf( '<ul class="hinata-result-bar__chips" aria-label="%1$s">%2$s</ul>', esc_attr__( '選択中の条件', 'hinata-realty' ), $items );
	}

	$sort = '';
	foreach ( hinata_sort_options() as $key => $label ) {
		$current = $key === $filters['sort'];
		$sort   .= sprintf(
			'<li><a href="%1$s"%2$s>%3$s</a></li>',
			$url( array( 'sort' => $key ) ),
			$current ? ' aria-current="true"' : '',
			esc_html( $label )
		);
	}

	return sprintf(
		'<div %1$s><div class="hinata-result-bar__row"><p class="hinata-result-bar__count" role="status"><span class="num">%2$s</span>%3$s</p><nav class="hinata-result-bar__sort" aria-label="%4$s"><ul>%5$s</ul></nav></div>%6$s</div>',
		get_block_wrapper_attributes(),
		esc_html( number_format( (int) $wp_query->found_posts ) ),
		esc_html__( '件の物件', 'hinata-realty' ),
		esc_attr__( '並べ替え', 'hinata-realty' ),
		$sort,
		$chips_html
	);
}

/**
 * 物件詳細の右段に置く主要項目（間取り・面積・駅徒歩・築年数・所在階）。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function hinata_render_property_summary( $attributes, $content, $block ) {
	$post_id = hinata_current_property_id( $block );
	if ( ! $post_id ) {
		return hinata_block_placeholder( __( '物件の主要項目', 'hinata-realty' ) );
	}
	$p     = hinata_get_property( $post_id );
	$items = array(
		array( 'layout', __( '間取り', 'hinata-realty' ), $p['layout'] ),
		array( 'area', __( '専有面積', 'hinata-realty' ), hinata_format_sqm( $p['sqm'] ) ),
		array( 'walk', __( '駅徒歩', 'hinata-realty' ), sprintf( '%s 徒歩%d分', $p['station'], $p['walk'] ) ),
		array( 'age', __( '築年数', 'hinata-realty' ), hinata_format_age( $p['built'] ) ),
		array( 'floor', __( '所在階', 'hinata-realty' ), sprintf( '%d階 ／ %d階建', $p['floor'], $p['floors'] ) ),
	);
	$html  = '';
	foreach ( $items as $item ) {
		list( $icon, $label, $value ) = $item;
		$html                        .= sprintf( '<div>%1$s<dt>%2$s</dt><dd>%3$s</dd></div>', hinata_icon( $icon ), esc_html( $label ), esc_html( $value ) );
	}
	return sprintf( '<dl %1$s>%2$s</dl>', get_block_wrapper_attributes(), $html );
}

/**
 * 物件概要表（2列の定義リスト）。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function hinata_render_property_spec( $attributes, $content, $block ) {
	$post_id = hinata_current_property_id( $block );
	if ( ! $post_id ) {
		return hinata_block_placeholder( __( '物件概要表', 'hinata-realty' ) );
	}
	$p    = hinata_get_property( $post_id );
	$age  = hinata_format_age( $p['built'] );
	$rows = array(
		__( '賃料', 'hinata-realty' )       => number_format( $p['rent'] ) . '円',
		__( '管理費・共益費', 'hinata-realty' )  => hinata_format_yen( $p['fee'] ),
		__( '敷金 ／ 礼金', 'hinata-realty' )  => hinata_format_months( $p['deposit'] ) . ' ／ ' . hinata_format_months( $p['key_money'] ),
		__( '間取り', 'hinata-realty' )      => $p['layout'],
		__( '専有面積', 'hinata-realty' )     => hinata_format_sqm( $p['sqm'] ),
		__( '所在階 ／ 階建', 'hinata-realty' ) => sprintf( '%d階 ／ %d階建', $p['floor'], $p['floors'] ),
		__( '築年月', 'hinata-realty' )      => trim( hinata_format_built( $p['built'] ) . ( $age ? '（' . $age . '）' : '' ) ),
		__( '交通', 'hinata-realty' )       => sprintf( '%s 徒歩%d分', $p['station'], $p['walk'] ),
		__( '所在地', 'hinata-realty' )      => $p['address'],
		__( 'エリア', 'hinata-realty' )      => $p['area_term'] ? $p['area_term']->name : '',
		__( '掲載状態', 'hinata-realty' )     => hinata_status_options()[ $p['status'] ] ?? '',
	);
	$html = '';
	foreach ( $rows as $label => $value ) {
		$html .= sprintf( '<div><dt>%1$s</dt><dd>%2$s</dd></div>', esc_html( $label ), esc_html( $value ) );
	}
	return sprintf( '<dl %1$s>%2$s</dl>', get_block_wrapper_attributes(), $html );
}

/**
 * 設備の一覧。該当するものを強調し、該当しないものを薄く示す。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function hinata_render_property_equipment( $attributes, $content, $block ) {
	$post_id = hinata_current_property_id( $block );
	if ( ! $post_id ) {
		return hinata_block_placeholder( __( '設備', 'hinata-realty' ) );
	}
	$has  = hinata_get_property( $post_id )['equipment'];
	$html = '';
	foreach ( hinata_equipment_options() as $key => $label ) {
		$on    = in_array( $key, $has, true );
		$html .= sprintf(
			'<li class="%1$s">%2$s<span>%3$s</span><span class="screen-reader-text">%4$s</span></li>',
			$on ? 'is-on' : 'is-off',
			hinata_icon( 'eq-' . str_replace( '_', '-', $key ) ),
			esc_html( $label ),
			$on ? esc_html__( '（あり）', 'hinata-realty' ) : esc_html__( '（なし）', 'hinata-realty' )
		);
	}
	return sprintf( '<ul %1$s>%2$s</ul>', get_block_wrapper_attributes(), $html );
}

/**
 * 問い合わせ導線。成約済みの物件では導線を出さず、その旨を表示する。
 *
 * 募集中の物件では、幅の狭い画面で画面下部に固定する電話・問い合わせのボタンも出力する。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function hinata_render_property_cta( $attributes, $content, $block ) {
	$post_id = hinata_current_property_id( $block );
	if ( ! $post_id ) {
		return hinata_block_placeholder( __( '問い合わせ導線', 'hinata-realty' ) );
	}
	if ( hinata_get_property( $post_id )['contracted'] ) {
		return sprintf(
			'<div %1$s><p class="hinata-cta__closed"><strong>%2$s</strong>%3$s</p><a class="hinata-cta__back" href="%4$s">%5$s</a></div>',
			get_block_wrapper_attributes( array( 'class' => 'is-contracted' ) ),
			esc_html__( 'この物件は成約済みです。', 'hinata-realty' ),
			esc_html__( '募集は終了しました。条件の近い物件をお探しします。', 'hinata-realty' ),
			esc_url( get_post_type_archive_link( 'property' ) ),
			esc_html__( '募集中の物件を見る', 'hinata-realty' )
		);
	}

	$store   = hinata_get_store();
	$contact = get_page_by_path( 'contact' );
	$inquiry = $contact ? add_query_arg( 'inquiry', $post_id, get_permalink( $contact ) ) : '';
	$tel     = 'tel:' . preg_replace( '/[^0-9+]/', '', $store['tel'] );

	$buttons = sprintf(
		'<a class="hinata-cta__primary" href="%1$s">%2$s</a><a class="hinata-cta__tel" href="%3$s">%4$s<span class="num">%5$s</span></a>',
		esc_url( $inquiry ),
		esc_html__( 'この物件について問い合わせる', 'hinata-realty' ),
		esc_url( $tel ),
		hinata_icon( 'tel' ),
		esc_html( $store['tel'] )
	);

	return sprintf(
		'<div %1$s><div class="hinata-cta__buttons">%2$s</div><p class="hinata-cta__note">%3$s</p><div class="hinata-cta__fixed" role="group" aria-label="%4$s"><a class="hinata-cta__tel" href="%5$s">%6$s%7$s</a><a class="hinata-cta__primary" href="%8$s">%9$s</a></div></div>',
		get_block_wrapper_attributes(),
		$buttons,
		/* translators: 1: 営業時間 2: 定休日 */
		esc_html( sprintf( __( '営業 %1$s ／ 定休 %2$s。内見のご予約も承ります。', 'hinata-realty' ), $store['hours'], $store['closed'] ) ),
		esc_attr__( '問い合わせ', 'hinata-realty' ),
		esc_url( $tel ),
		hinata_icon( 'tel' ),
		esc_html__( '電話する', 'hinata-realty' ),
		esc_url( $inquiry ),
		esc_html__( '問い合わせる', 'hinata-realty' )
	);
}

/**
 * エリアの一覧。エリアごとに掲載中の物件数とエリア別一覧への導線を置く。
 *
 * @return string HTML。
 */
function hinata_render_area_list() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'area',
			'hide_empty' => false,
			'orderby'    => 'term_id',
		)
	);
	$html  = '';
	foreach ( $terms as $term ) {
		$count = new WP_Query(
			array(
				'post_type'      => 'property',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- エリアごとの件数に必要なため。
					array(
						'taxonomy' => 'area',
						'terms'    => $term->term_id,
					),
				),
				'meta_query'     => array( hinata_available_clause() ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 成約済みを除くために必要なため。
			)
		);
		$html .= sprintf(
			'<li><a href="%1$s"><span class="hinata-area-list__name">%2$s</span><span class="hinata-area-list__desc">%3$s</span><span class="hinata-area-list__count"><span class="num">%4$d</span>件</span></a></li>',
			esc_url( get_term_link( $term ) ),
			esc_html( $term->name ),
			esc_html( $term->description ),
			(int) $count->found_posts
		);
	}
	return sprintf( '<ul %1$s>%2$s</ul>', get_block_wrapper_attributes(), $html );
}

/**
 * 店舗情報の定義リスト。
 *
 * @return string HTML。
 */
function hinata_render_store_info() {
	$store = hinata_get_store();
	$rows  = array(
		__( '店舗', 'hinata-realty' )   => get_bloginfo( 'name' ),
		__( '所在地', 'hinata-realty' )  => $store['address'],
		__( '電話番号', 'hinata-realty' ) => $store['tel'],
		__( '営業時間', 'hinata-realty' ) => $store['hours'],
		__( '定休日', 'hinata-realty' )  => $store['closed'],
	);
	$html  = '';
	foreach ( $rows as $label => $value ) {
		$html .= sprintf( '<div><dt>%1$s</dt><dd>%2$s</dd></div>', esc_html( $label ), esc_html( $value ) );
	}
	return sprintf( '<dl %1$s>%2$s</dl>', get_block_wrapper_attributes(), $html );
}

/**
 * 問い合わせフォーム（表示のみ）。物件詳細から来た場合は、対象の物件名を初期値とする。
 *
 * @return string HTML。
 */
function hinata_render_contact_form() {
	$inquiry = absint( get_query_var( 'inquiry' ) );
	$title   = $inquiry && 'property' === get_post_type( $inquiry ) ? get_the_title( $inquiry ) : '';
	$uid     = wp_unique_id( 'hinata-contact-' );

	$fields = array(
		array( 'name', __( 'お名前', 'hinata-realty' ), 'text', true, 'name' ),
		array( 'email', __( 'メールアドレス', 'hinata-realty' ), 'email', true, 'email' ),
		array( 'tel', __( '電話番号', 'hinata-realty' ), 'tel', false, 'tel' ),
		array( 'property', __( 'お問い合わせの物件', 'hinata-realty' ), 'text', false, 'off' ),
	);
	$html   = '';
	foreach ( $fields as $field ) {
		list( $name, $label, $type, $required, $autocomplete ) = $field;
		$html .= sprintf(
			'<div class="hinata-contact__field"><label for="%1$s-%2$s">%3$s%4$s</label><input id="%1$s-%2$s" name="%2$s" type="%5$s" autocomplete="%6$s" value="%7$s"%8$s></div>',
			esc_attr( $uid ),
			esc_attr( $name ),
			esc_html( $label ),
			$required ? '<span class="hinata-contact__req">' . esc_html__( '必須', 'hinata-realty' ) . '</span>' : '',
			esc_attr( $type ),
			esc_attr( $autocomplete ),
			'property' === $name ? esc_attr( $title ) : '',
			$required ? ' required' : ''
		);
	}
	$html .= sprintf(
		'<div class="hinata-contact__field"><label for="%1$s-message">%2$s</label><textarea id="%1$s-message" name="message" rows="6"></textarea></div>',
		esc_attr( $uid ),
		esc_html__( 'ご希望・ご質問', 'hinata-realty' )
	);

	return sprintf(
		'<div %1$s><p class="hinata-contact__notice">%2$s</p><form class="hinata-contact__form" action="#" method="post" aria-describedby="%3$s-notice">%4$s<button type="submit" disabled>%5$s</button></form><p class="screen-reader-text" id="%3$s-notice">%2$s</p></div>',
		get_block_wrapper_attributes(),
		esc_html__( 'このフォームは制作サンプルのため表示のみです。送信はできません。', 'hinata-realty' ),
		esc_attr( $uid ),
		$html,
		esc_html__( '送信する', 'hinata-realty' )
	);
}
