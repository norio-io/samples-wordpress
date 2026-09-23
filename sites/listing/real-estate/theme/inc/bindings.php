<?php
/**
 * ブロックバインディング
 *
 * - hinata/property: 物件の入力欄を表示用の書式に整えて返す（賃料「7.8万円」、面積「32.4㎡」など）
 * - hinata/store:    店舗情報（設定 → 店舗情報）を返す。ヘッダー・フッター・トップ・会社概要で共有する
 * - hinata/url:      サイト内の URL を返す。Playground のサイト URL はスコープのパスを含むため、
 *                    テンプレートにルート相対のパスを書かず、ここで解決する
 *
 * @package hinata-realty
 */

/**
 * バインディングのソースを登録する。
 */
function hinata_register_binding_sources() {
	register_block_bindings_source(
		'hinata/property',
		array(
			'label'              => __( '物件情報', 'hinata-realty' ),
			'get_value_callback' => 'hinata_binding_property',
			'uses_context'       => array( 'postId', 'postType' ),
		)
	);
	register_block_bindings_source(
		'hinata/store',
		array(
			'label'              => __( '店舗情報', 'hinata-realty' ),
			'get_value_callback' => 'hinata_binding_store',
		)
	);
	register_block_bindings_source(
		'hinata/url',
		array(
			'label'              => __( 'サイト内の URL', 'hinata-realty' ),
			'get_value_callback' => 'hinata_binding_url',
		)
	);
}
add_action( 'init', 'hinata_register_binding_sources' );

/**
 * 物件の入力欄を表示用の書式で返す。
 *
 * @param array    $source_args 引数。key に項目名を指定する。
 * @param WP_Block $block       ブロック。
 * @return string|null 表示用の文字列。
 */
function hinata_binding_property( array $source_args, $block ) {
	$post_id = $block->context['postId'] ?? get_the_ID();
	if ( ! $post_id || 'property' !== get_post_type( $post_id ) ) {
		return null;
	}
	$p = hinata_get_property( $post_id );

	switch ( $source_args['key'] ?? '' ) {
		case 'rent':
			return esc_html( hinata_format_man_yen( $p['rent'] ) );
		case 'fee':
			return esc_html( sprintf( '管理費 %s', hinata_format_yen( $p['fee'] ) ) );
		case 'layout':
			return esc_html( $p['layout'] );
		case 'sqm':
			return esc_html( hinata_format_sqm( $p['sqm'] ) );
		case 'walk':
			return esc_html( sprintf( '%s 徒歩%d分', $p['station'], $p['walk'] ) );
		case 'walk_short':
			return esc_html( sprintf( '徒歩%d分', $p['walk'] ) );
		case 'age':
			return esc_html( hinata_format_age( $p['built'] ) );
		case 'address':
			return esc_html( $p['address'] );
		case 'area':
			return $p['area_term'] ? esc_html( $p['area_term']->name ) : '';
		case 'new':
			// 新着・成約済みの札。該当しない場合は空文字列とし、CSS で札ごと隠す。
			return $p['is_new'] && ! $p['contracted'] ? esc_html__( '新着', 'hinata-realty' ) : '';
		case 'contracted':
			return $p['contracted'] ? esc_html__( '成約済み', 'hinata-realty' ) : '';
	}
	return null;
}

/**
 * 店舗情報の既定値。
 *
 * @return array<string, string> 項目と値。
 */
function hinata_store_defaults() {
	return array(
		'address' => '〒000-0000 晴野市晴野本町1-2-3',
		'tel'     => '000-000-0000',
		'hours'   => '10:00–19:00',
		'closed'  => '水曜日',
	);
}

/**
 * 店舗情報を取得する。
 *
 * @return array<string, string> 項目と値。
 */
function hinata_get_store() {
	$saved = get_option( 'hinata_store', array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), hinata_store_defaults() );
}

/**
 * 店舗情報を返す。
 *
 * @param array $source_args 引数。key に項目名（address / tel / hours / closed / hours_closed / tel_url）を指定する。
 * @return string|null 表示用の文字列。
 */
function hinata_binding_store( array $source_args ) {
	$store = hinata_get_store();
	$key   = $source_args['key'] ?? '';
	if ( 'tel_url' === $key ) {
		return esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $store['tel'] ) );
	}
	if ( 'hours_closed' === $key ) {
		return esc_html( sprintf( '営業 %s ／ 定休 %s', $store['hours'], $store['closed'] ) );
	}
	return isset( $store[ $key ] ) ? esc_html( $store[ $key ] ) : null;
}

/**
 * サイト内の URL を返す。
 *
 * @param array $source_args 引数。key に properties（物件一覧）、areas（トップのエリア）、
 *                           または固定ページのスラッグを指定する。
 * @return string|null URL。
 */
function hinata_binding_url( array $source_args ) {
	$key = $source_args['key'] ?? '';
	if ( 'properties' === $key ) {
		return esc_url( get_post_type_archive_link( 'property' ) );
	}
	if ( 'areas' === $key ) {
		return esc_url( home_url( '/#areas' ) );
	}
	if ( 'home' === $key ) {
		return esc_url( home_url( '/' ) );
	}
	$page = $key ? get_page_by_path( $key ) : null;
	return $page ? esc_url( get_permalink( $page ) ) : null;
}
