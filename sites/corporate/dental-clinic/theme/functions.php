<?php
/**
 * みずき歯科クリニック（架空）のテーマ機能
 *
 * @package mizuki-dental
 */

/**
 * エディターと公開側で同一のスタイルシートを読み込む。
 * 表示の差をなくすため、エディター用のスタイルを別に持たない。
 */
function mizuki_dental_setup() {
	add_editor_style( 'assets/css/theme.css' );
}
add_action( 'after_setup_theme', 'mizuki_dental_setup' );

/**
 * 公開側のスタイルシートを読み込む。
 */
function mizuki_dental_enqueue_styles() {
	wp_enqueue_style(
		'mizuki-dental',
		get_theme_file_uri( 'assets/css/theme.css' ),
		array(),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'mizuki_dental_enqueue_styles' );

/**
 * 固定ページの見出し部に用いる項目を登録する。
 *
 * - 抜粋: 見出しの下のリード文
 * - mizuki_en: 見出しの上の英字ラベル（ブロックバインディングで表示する）
 */
function mizuki_dental_register_page_fields() {
	add_post_type_support( 'page', 'excerpt' );

	register_post_meta(
		'page',
		'mizuki_en',
		array(
			'type'              => 'string',
			'label'             => __( '英字ラベル', 'mizuki-dental' ),
			'single'            => true,
			'show_in_rest'      => true,
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
}
add_action( 'init', 'mizuki_dental_register_page_fields' );

/**
 * 固定ページへの URL を、スラッグから解決するバインディングを登録する。
 *
 * テンプレートパーツのボタンは全ページで共有されるため、相対パスでは解決できない。
 * WordPress Playground はサイトの URL にスコープのパスを含むため、ルート相対のパスも用いない。
 */
function mizuki_dental_register_bindings() {
	register_block_bindings_source(
		'mizuki-dental/page-url',
		array(
			'label'              => __( '固定ページの URL', 'mizuki-dental' ),
			'get_value_callback' => 'mizuki_dental_get_page_url',
		)
	);
}
add_action( 'init', 'mizuki_dental_register_bindings' );

/**
 * バインディングの引数 path に一致する固定ページの URL を返す。
 *
 * @param array $source_args バインディングの引数。
 * @return string|null 固定ページの URL。該当がなければトップの URL。
 */
function mizuki_dental_get_page_url( array $source_args ) {
	if ( empty( $source_args['path'] ) ) {
		return null;
	}
	$page = get_page_by_path( $source_args['path'] );
	return esc_url( $page ? get_permalink( $page ) : home_url( '/' ) );
}

/**
 * ブロックスタイルを登録する。見た目は assets/css/theme.css に置く。
 */
function mizuki_dental_register_block_styles() {
	$styles = array(
		'core/paragraph' => array(
			'eyebrow' => __( '英字ラベル', 'mizuki-dental' ),
			'note'    => __( '注記', 'mizuki-dental' ),
		),
		'core/list'      => array(
			'checks' => __( 'チェック', 'mizuki-dental' ),
			'routes' => __( '経路', 'mizuki-dental' ),
		),
		'core/table'     => array(
			'hours' => __( '診療時間', 'mizuki-dental' ),
			'price' => __( '料金表', 'mizuki-dental' ),
			'defs'  => __( '項目と説明', 'mizuki-dental' ),
		),
		'core/button'    => array(
			'link' => __( 'テキストリンク', 'mizuki-dental' ),
		),
		'core/group'     => array(
			'card'   => __( 'カード', 'mizuki-dental' ),
			'person' => __( '人物', 'mizuki-dental' ),
			'flow'   => __( '手順', 'mizuki-dental' ),
			'faq'    => __( 'よくある質問', 'mizuki-dental' ),
		),
	);
	foreach ( $styles as $block_name => $block_styles ) {
		foreach ( $block_styles as $name => $label ) {
			register_block_style(
				$block_name,
				array(
					'name'  => $name,
					'label' => $label,
				)
			);
		}
	}
}
add_action( 'init', 'mizuki_dental_register_block_styles' );

/**
 * お知らせの詳細とカテゴリーの一覧で、ナビゲーションの「お知らせ」を現在地として示す。
 *
 * core は表示中のページそのものへのリンクにだけ現在地の印を付けるため、
 * 投稿ページ（お知らせ）の配下にあたるページでは補う。ページそのものではないため aria-current は true とする。
 *
 * @param string $block_content ナビゲーションリンクの HTML。
 * @param array  $block         ブロック。
 * @return string 現在地の印を補った HTML。
 */
function mizuki_dental_mark_news_section( $block_content, $block ) {
	$page_for_posts = (int) get_option( 'page_for_posts' );
	$is_news_child  = is_singular( 'post' ) || is_category();
	if ( ! $page_for_posts || ! $is_news_child || (int) ( $block['attrs']['id'] ?? 0 ) !== $page_for_posts ) {
		return $block_content;
	}
	$tags = new WP_HTML_Tag_Processor( $block_content );
	if ( $tags->next_tag( 'li' ) ) {
		$tags->add_class( 'current-menu-item' );
	}
	if ( $tags->next_tag( 'a' ) ) {
		$tags->set_attribute( 'aria-current', 'true' );
	}
	return $tags->get_updated_html();
}
add_filter( 'render_block_core/navigation-link', 'mizuki_dental_mark_news_section', 10, 2 );

/**
 * パターンのカテゴリーを登録する。
 */
function mizuki_dental_register_pattern_category() {
	register_block_pattern_category(
		'mizuki-dental',
		array( 'label' => __( 'みずき歯科クリニック', 'mizuki-dental' ) )
	);
}
add_action( 'init', 'mizuki_dental_register_pattern_category' );

/**
 * タイトルの区切りを静的サンプルと揃える。
 *
 * @return string 区切り文字。
 */
function mizuki_dental_title_separator() {
	return '—';
}
add_filter( 'document_title_separator', 'mizuki_dental_title_separator' );

// 制作サンプルのため、検索エンジンに登録させない。
add_filter( 'wp_robots', 'wp_robots_no_robots' );
