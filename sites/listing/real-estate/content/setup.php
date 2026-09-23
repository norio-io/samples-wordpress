<?php
/**
 * デモデータの初期設定
 *
 * blueprint の runPHP から、WXR の取り込み後に1回だけ実行する。
 *
 * - 間取り図（floorplans/*.png）をメディアに登録し、物件のアイキャッチ画像とする
 * - 物件の掲載日を「実行した日から数えた日数」に置き換える。いつ開いても「新着」の物件があるようにするため
 * - 店舗情報の初期値を保存する
 *
 * 架空の題材であり、物件名・所在地・電話番号・間取り図はすべてサンプル用に作成したものです。
 *
 * @package hinata-realty
 */

// メディアの登録に用いる関数を読み込む。
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$hinata_demo_dir = __DIR__ . '/floorplans/';
$hinata_now      = time();

$hinata_properties = get_posts(
	array(
		'post_type'      => 'property',
		'post_status'    => 'any',
		'posts_per_page' => -1,
	)
);

foreach ( $hinata_properties as $hinata_property ) {
	// 間取り図。取り込み元のファイルを残すため、一時ファイルに複製してから登録する。
	$hinata_file = (string) get_post_meta( $hinata_property->ID, '_demo_floorplan', true );
	if ( $hinata_file && is_readable( $hinata_demo_dir . $hinata_file ) ) {
		$hinata_tmp = wp_tempnam( $hinata_file );
		copy( $hinata_demo_dir . $hinata_file, $hinata_tmp );
		$hinata_layout = get_the_terms( $hinata_property->ID, 'layout' );
		$hinata_image  = media_handle_sideload(
			array(
				'name'     => $hinata_file,
				'tmp_name' => $hinata_tmp,
			),
			$hinata_property->ID,
			/* translators: %s: 物件名 */
			sprintf( '%s 間取り図', $hinata_property->post_title )
		);
		if ( ! is_wp_error( $hinata_image ) ) {
			set_post_thumbnail( $hinata_property->ID, $hinata_image );
			update_post_meta(
				$hinata_image,
				'_wp_attachment_image_alt',
				sprintf( '%s の間取り図（%s）', $hinata_property->post_title, is_array( $hinata_layout ) && $hinata_layout ? $hinata_layout[0]->name : '' )
			);
		}
	}

	// 掲載日。
	$hinata_days = (int) get_post_meta( $hinata_property->ID, '_demo_days_ago', true );
	$hinata_date = $hinata_now - $hinata_days * DAY_IN_SECONDS;
	wp_update_post(
		array(
			'ID'            => $hinata_property->ID,
			'post_date'     => wp_date( 'Y-m-d H:i:s', $hinata_date ),
			'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $hinata_date ),
		)
	);

	delete_post_meta( $hinata_property->ID, '_demo_floorplan' );
	delete_post_meta( $hinata_property->ID, '_demo_days_ago' );
}

update_option( 'hinata_store', hinata_store_defaults() );
