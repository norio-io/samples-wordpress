<?php
/**
 * ブロックバインディング
 *
 * - moegi/contact: 採用窓口（設定 → 採用窓口）の情報を返す。ヘッダー・フッター・トップ・求人の詳細・応募フォームで共有する
 * - moegi/url:     サイト内の URL を返す。Playground のサイト URL はスコープのパスを含むため、
 *                  テンプレートにルート相対のパスを書かず、ここで解決する
 *
 * @package moegi-recruit
 */

/**
 * バインディングのソースを登録する。
 */
function moegi_register_binding_sources() {
	register_block_bindings_source(
		'moegi/contact',
		array(
			'label'              => __( '採用窓口', 'moegi-recruit' ),
			'get_value_callback' => 'moegi_binding_contact',
		)
	);
	register_block_bindings_source(
		'moegi/url',
		array(
			'label'              => __( 'サイト内の URL', 'moegi-recruit' ),
			'get_value_callback' => 'moegi_binding_url',
		)
	);
}
add_action( 'init', 'moegi_register_binding_sources' );

/**
 * 採用窓口の情報を返す。
 *
 * @param array $source_args 引数。key に項目名（department / tel / hours / tel_url / hours_label / desk）を指定する。
 * @return string|null 表示用の文字列。
 */
function moegi_binding_contact( array $source_args ) {
	$contact = moegi_get_contact();
	$key     = $source_args['key'] ?? '';
	if ( 'tel_url' === $key ) {
		return esc_url( moegi_tel_url() );
	}
	if ( 'hours_label' === $key ) {
		/* translators: %s: 受付時間 */
		return esc_html( sprintf( __( '受付 %s', 'moegi-recruit' ), $contact['hours'] ) );
	}
	if ( 'desk' === $key ) {
		/* translators: 1: 担当部署 2: 受付時間 */
		return esc_html( sprintf( __( '%1$s ／ 受付 %2$s', 'moegi-recruit' ), $contact['department'], $contact['hours'] ) );
	}
	return in_array( $key, array( 'department', 'tel', 'hours' ), true ) ? esc_html( $contact[ $key ] ) : null;
}

/**
 * サイト内の URL を返す。
 *
 * @param array $source_args 引数。key に jobs（求人一覧）、facilities（施設一覧）、voices（職員の声の一覧）、
 *                           home（トップ）、または固定ページのスラッグを指定する。
 * @return string|null URL。
 */
function moegi_binding_url( array $source_args ) {
	$key      = $source_args['key'] ?? '';
	$archives = array(
		'jobs'       => 'job',
		'facilities' => 'facility',
		'voices'     => 'voice',
	);
	if ( isset( $archives[ $key ] ) ) {
		return esc_url( get_post_type_archive_link( $archives[ $key ] ) );
	}
	if ( 'home' === $key ) {
		return esc_url( home_url( '/' ) );
	}
	$page = $key ? get_page_by_path( $key ) : null;
	return $page ? esc_url( get_permalink( $page ) ) : null;
}
