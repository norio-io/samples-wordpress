<?php
/**
 * ひびき英語スクール（架空）のテーマ機能
 *
 * 機能ごとに inc/ 配下へ分けて読み込む。
 *
 * @package hibiki-english
 */

// 機能ごとのファイルを読み込む。
require_once __DIR__ . '/inc/data.php';
require_once __DIR__ . '/inc/post-types.php';
require_once __DIR__ . '/inc/query.php';
require_once __DIR__ . '/inc/bindings.php';
require_once __DIR__ . '/inc/blocks.php';
require_once __DIR__ . '/inc/admin.php';

/**
 * エディターと公開側で同一のスタイルシートを読み込む。
 */
function hibiki_setup() {
	add_editor_style( 'assets/css/theme.css' );
}
add_action( 'after_setup_theme', 'hibiki_setup' );

/**
 * 公開側のスタイルシートとスクリプトを読み込む。
 */
function hibiki_enqueue_assets() {
	$version = wp_get_theme()->get( 'Version' );
	wp_enqueue_style( 'hibiki-english', get_theme_file_uri( 'assets/css/theme.css' ), array(), $version );
	wp_enqueue_script( 'hibiki-english', get_theme_file_uri( 'assets/js/front.js' ), array(), $version, array( 'strategy' => 'defer' ) );
}
add_action( 'wp_enqueue_scripts', 'hibiki_enqueue_assets' );

/**
 * タイトルの区切りを揃える。
 *
 * @return string 区切り文字。
 */
function hibiki_title_separator() {
	return '—';
}
add_filter( 'document_title_separator', 'hibiki_title_separator' );

// 制作サンプルのため、検索エンジンに登録させない。
add_filter( 'wp_robots', 'wp_robots_no_robots' );
