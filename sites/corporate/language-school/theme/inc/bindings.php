<?php
/**
 * ブロックバインディング
 *
 * - hibiki/school: 教室情報（設定 → 教室情報）を返す。ヘッダー・フッター・トップ・スクール紹介・体験レッスンで共有する
 * - hibiki/url:    サイト内の URL を返す。Playground のサイト URL はスコープのパスを含むため、
 *                  テンプレートにルート相対のパスを書かず、ここで解決する
 *
 * @package hibiki-english
 */

/**
 * バインディングのソースを登録する。
 */
function hibiki_register_binding_sources() {
	register_block_bindings_source(
		'hibiki/school',
		array(
			'label'              => __( '教室情報', 'hibiki-english' ),
			'get_value_callback' => 'hibiki_binding_school',
		)
	);
	register_block_bindings_source(
		'hibiki/url',
		array(
			'label'              => __( 'サイト内の URL', 'hibiki-english' ),
			'get_value_callback' => 'hibiki_binding_url',
		)
	);
}
add_action( 'init', 'hibiki_register_binding_sources' );

/**
 * 教室情報を返す。
 *
 * @param array $source_args 引数。key に項目名（address / tel / hours / closed / closed_label / hours_closed / tel_url）を指定する。
 * @return string|null 表示用の文字列。
 */
function hibiki_binding_school( array $source_args ) {
	$school = hibiki_get_school();
	$key    = $source_args['key'] ?? '';
	if ( 'tel_url' === $key ) {
		return esc_url( hibiki_tel_url() );
	}
	if ( 'closed_label' === $key ) {
		/* translators: %s: 休校日 */
		return esc_html( sprintf( __( '休校日：%s', 'hibiki-english' ), $school['closed'] ) );
	}
	if ( 'hours_closed' === $key ) {
		/* translators: 1: 受付時間 2: 休校日 */
		return esc_html( sprintf( __( '受付 %1$s ／ 休校 %2$s', 'hibiki-english' ), $school['hours'], $school['closed'] ) );
	}
	return isset( $school[ $key ] ) ? esc_html( $school[ $key ] ) : null;
}

/**
 * サイト内の URL を返す。
 *
 * @param array $source_args 引数。key に courses（コース一覧）、instructors（講師一覧）、home（トップ）、
 *                           または固定ページのスラッグを指定する。
 * @return string|null URL。
 */
function hibiki_binding_url( array $source_args ) {
	$key = $source_args['key'] ?? '';
	if ( 'courses' === $key ) {
		return esc_url( get_post_type_archive_link( 'course' ) );
	}
	if ( 'instructors' === $key ) {
		return esc_url( get_post_type_archive_link( 'instructor' ) );
	}
	if ( 'home' === $key ) {
		return esc_url( home_url( '/' ) );
	}
	$page = $key ? get_page_by_path( $key ) : null;
	return $page ? esc_url( get_permalink( $page ) ) : null;
}
