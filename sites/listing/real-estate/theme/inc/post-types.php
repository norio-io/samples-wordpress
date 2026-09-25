<?php
/**
 * 物件（カスタム投稿タイプ）と分類・入力欄の登録
 *
 * @package hinata-realty
 */

/**
 * 物件の投稿タイプと、エリア・間取りの分類を登録する。
 */
function hinata_register_post_types() {
	register_post_type(
		'property',
		array(
			'labels'        => array(
				'name'               => __( '物件', 'hinata-realty' ),
				'singular_name'      => __( '物件', 'hinata-realty' ),
				'add_new'            => __( '物件を追加', 'hinata-realty' ),
				'add_new_item'       => __( '物件を追加', 'hinata-realty' ),
				'edit_item'          => __( '物件を編集', 'hinata-realty' ),
				'all_items'          => __( '物件一覧', 'hinata-realty' ),
				'archives'           => __( '物件一覧', 'hinata-realty' ),
				'search_items'       => __( '物件を検索', 'hinata-realty' ),
				'not_found'          => __( '物件が見つかりません', 'hinata-realty' ),
				'not_found_in_trash' => __( 'ゴミ箱に物件はありません', 'hinata-realty' ),
			),
			'public'        => true,
			'has_archive'   => 'properties',
			'rewrite'       => array(
				'slug'       => 'properties',
				'with_front' => false,
			),
			'menu_icon'     => 'dashicons-building',
			'menu_position' => 5,
			'show_in_rest'  => true,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'revisions' ),
			// 本文は「おすすめポイント」の段落1つに固定する。物件ページのレイアウトはテンプレートが担い、
			// 更新担当者は入力欄と文章だけを編集するため、ブロックの追加・移動を許さない。
			'template'      => array(
				array(
					'core/paragraph',
					array( 'placeholder' => __( 'おすすめポイントを入力（任意）', 'hinata-realty' ) ),
				),
			),
			'template_lock' => 'insert',
		)
	);

	$taxonomy_args = array(
		'public'            => true,
		'hierarchical'      => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		// 絞り込みの URL のクエリ（area / layout）はテーマが扱う。分類の既定のクエリ変数と競合させない。
		'query_var'         => false,
	);

	register_taxonomy(
		'area',
		'property',
		array_merge(
			$taxonomy_args,
			array(
				'labels'  => array(
					'name'          => __( 'エリア', 'hinata-realty' ),
					'singular_name' => __( 'エリア', 'hinata-realty' ),
					'all_items'     => __( 'すべてのエリア', 'hinata-realty' ),
					'edit_item'     => __( 'エリアを編集', 'hinata-realty' ),
					'add_new_item'  => __( 'エリアを追加', 'hinata-realty' ),
				),
				'rewrite' => array(
					'slug'       => 'area',
					'with_front' => false,
				),
			)
		)
	);

	register_taxonomy(
		'layout',
		'property',
		array_merge(
			$taxonomy_args,
			array(
				'labels'  => array(
					'name'          => __( '間取り', 'hinata-realty' ),
					'singular_name' => __( '間取り', 'hinata-realty' ),
					'all_items'     => __( 'すべての間取り', 'hinata-realty' ),
					'edit_item'     => __( '間取りを編集', 'hinata-realty' ),
					'add_new_item'  => __( '間取りを追加', 'hinata-realty' ),
				),
				'rewrite' => array(
					'slug'       => 'layout',
					'with_front' => false,
				),
			)
		)
	);
}
add_action( 'init', 'hinata_register_post_types' );

/**
 * 物件の入力欄を登録する。型を定義し、REST API に公開してエディターから編集できるようにする。
 */
function hinata_register_property_meta() {
	$fields = array(
		'rent'      => array( 'integer', __( '賃料（円）', 'hinata-realty' ), 0 ),
		'fee'       => array( 'integer', __( '管理費・共益費（円）', 'hinata-realty' ), 0 ),
		'deposit'   => array( 'number', __( '敷金（か月）', 'hinata-realty' ), 0 ),
		'key_money' => array( 'number', __( '礼金（か月）', 'hinata-realty' ), 0 ),
		'sqm'       => array( 'number', __( '専有面積（㎡）', 'hinata-realty' ), 0 ),
		'floor'     => array( 'integer', __( '所在階', 'hinata-realty' ), 1 ),
		'floors'    => array( 'integer', __( '建物の階数', 'hinata-realty' ), 1 ),
		'built'     => array( 'string', __( '築年月（YYYY-MM）', 'hinata-realty' ), '' ),
		'station'   => array( 'string', __( '最寄り駅', 'hinata-realty' ), '' ),
		'walk'      => array( 'integer', __( '駅徒歩（分）', 'hinata-realty' ), 0 ),
		'address'   => array( 'string', __( '所在地', 'hinata-realty' ), '' ),
	);

	foreach ( $fields as $key => $field ) {
		list( $type, $label, $fallback ) = $field;
		register_post_meta(
			'property',
			$key,
			array(
				'type'              => $type,
				'label'             => $label,
				'single'            => true,
				'default'           => $fallback,
				'show_in_rest'      => true,
				'sanitize_callback' => 'hinata_sanitize_meta_' . $type,
			)
		);
	}

	register_post_meta(
		'property',
		'status',
		array(
			'type'              => 'string',
			'label'             => __( '掲載状態', 'hinata-realty' ),
			'single'            => true,
			'default'           => 'available',
			'show_in_rest'      => array(
				'schema' => array( 'enum' => array_keys( hinata_status_options() ) ),
			),
			'sanitize_callback' => 'hinata_sanitize_status',
		)
	);

	// 設備は値ごとに1行で保存する（single = false）。1件の物件が複数の設備を持つため。
	register_post_meta(
		'property',
		'equipment',
		array(
			'type'         => 'string',
			'label'        => __( '設備', 'hinata-realty' ),
			'single'       => false,
			'show_in_rest' => array(
				'schema' => array( 'enum' => array_keys( hinata_equipment_options() ) ),
			),
		)
	);
}
add_action( 'init', 'hinata_register_property_meta' );

/**
 * 整数の入力欄を整える。負の値は 0 とする。
 *
 * @param mixed $value 入力値。
 * @return int 整えた値。
 */
function hinata_sanitize_meta_integer( $value ) {
	return max( 0, (int) $value );
}

/**
 * 数値の入力欄を整える。負の値は 0 とする。
 *
 * @param mixed $value 入力値。
 * @return float 整えた値。
 */
function hinata_sanitize_meta_number( $value ) {
	return max( 0, (float) $value );
}

/**
 * 文字列の入力欄を整える。
 *
 * @param mixed $value 入力値。
 * @return string 整えた値。
 */
function hinata_sanitize_meta_string( $value ) {
	return sanitize_text_field( (string) $value );
}

/**
 * 掲載状態を整える。選択肢にない値は「募集中」とする。
 *
 * @param mixed $value 入力値。
 * @return string 整えた値。
 */
function hinata_sanitize_status( $value ) {
	return array_key_exists( (string) $value, hinata_status_options() ) ? (string) $value : 'available';
}

/**
 * 物件のスラッグが日本語（URL エンコードされた文字列）になる場合は、property-<ID> とする。
 *
 * 物件名は日本語で入力されるため、既定ではスラッグが読めない長い URL になる。
 * 英数字のスラッグを入力した場合はそのまま用いる。
 *
 * @param string $slug      一意化したスラッグ。
 * @param int    $post_id   投稿 ID。
 * @param string $status    投稿の状態。
 * @param string $post_type 投稿タイプ。
 * @return string スラッグ。
 */
function hinata_property_slug( $slug, $post_id, $status, $post_type ) {
	if ( 'property' !== $post_type || ! $post_id || ! str_contains( $slug, '%' ) ) {
		return $slug;
	}
	return 'property-' . $post_id;
}
add_filter( 'wp_unique_post_slug', 'hinata_property_slug', 10, 4 );
