<?php
/**
 * 求人・施設・職員の声（カスタム投稿タイプ）と分類・入力欄の登録
 *
 * @package moegi-recruit
 */

/**
 * 求人・施設・職員の声の投稿タイプと、職種・雇用形態の分類を登録する。
 */
function moegi_register_post_types() {
	// 本文は段落1つに固定する。ページのレイアウトはテンプレートが担い、採用担当者は入力欄と文章だけを
	// 編集するため、ブロックの追加・移動を許さない。
	$paragraph = static function ( $placeholder ) {
		return array(
			'template'      => array( array( 'core/paragraph', array( 'placeholder' => $placeholder ) ) ),
			'template_lock' => 'insert',
		);
	};

	register_post_type(
		'job',
		array_merge(
			array(
				'labels'        => array(
					'name'               => __( '求人', 'moegi-recruit' ),
					'singular_name'      => __( '求人', 'moegi-recruit' ),
					'add_new'            => __( '求人を追加', 'moegi-recruit' ),
					'add_new_item'       => __( '求人を追加', 'moegi-recruit' ),
					'edit_item'          => __( '求人を編集', 'moegi-recruit' ),
					'all_items'          => __( '求人一覧', 'moegi-recruit' ),
					'archives'           => __( '求人一覧', 'moegi-recruit' ),
					'search_items'       => __( '求人を検索', 'moegi-recruit' ),
					'not_found'          => __( '求人が見つかりません', 'moegi-recruit' ),
					'not_found_in_trash' => __( 'ゴミ箱に求人はありません', 'moegi-recruit' ),
				),
				'public'        => true,
				'has_archive'   => 'jobs',
				'rewrite'       => array(
					'slug'       => 'jobs',
					'with_front' => false,
				),
				'menu_icon'     => 'dashicons-id-alt',
				'menu_position' => 5,
				'show_in_rest'  => true,
				'supports'      => array( 'title', 'editor', 'excerpt', 'custom-fields', 'revisions' ),
			),
			$paragraph( __( '仕事内容を入力', 'moegi-recruit' ) )
		)
	);

	register_post_type(
		'facility',
		array_merge(
			array(
				'labels'        => array(
					'name'               => __( '施設', 'moegi-recruit' ),
					'singular_name'      => __( '施設', 'moegi-recruit' ),
					'add_new'            => __( '施設を追加', 'moegi-recruit' ),
					'add_new_item'       => __( '施設を追加', 'moegi-recruit' ),
					'edit_item'          => __( '施設を編集', 'moegi-recruit' ),
					'all_items'          => __( '施設一覧', 'moegi-recruit' ),
					'archives'           => __( '施設紹介', 'moegi-recruit' ),
					'search_items'       => __( '施設を検索', 'moegi-recruit' ),
					'not_found'          => __( '施設が見つかりません', 'moegi-recruit' ),
					'not_found_in_trash' => __( 'ゴミ箱に施設はありません', 'moegi-recruit' ),
				),
				'public'        => true,
				'has_archive'   => 'facilities',
				'rewrite'       => array(
					'slug'       => 'facilities',
					'with_front' => false,
				),
				'menu_icon'     => 'dashicons-building',
				'menu_position' => 6,
				'show_in_rest'  => true,
				// 「順序」（page-attributes）を、施設を並べる順に用いる。
				'supports'      => array( 'title', 'editor', 'excerpt', 'custom-fields', 'page-attributes', 'revisions' ),
			),
			$paragraph( __( '施設の紹介を入力', 'moegi-recruit' ) )
		)
	);

	register_post_type(
		'voice',
		array_merge(
			array(
				'labels'        => array(
					'name'               => __( '職員の声', 'moegi-recruit' ),
					'singular_name'      => __( '職員の声', 'moegi-recruit' ),
					'add_new'            => __( '職員の声を追加', 'moegi-recruit' ),
					'add_new_item'       => __( '職員の声を追加', 'moegi-recruit' ),
					'edit_item'          => __( '職員の声を編集', 'moegi-recruit' ),
					'all_items'          => __( '職員の声一覧', 'moegi-recruit' ),
					'archives'           => __( '職員の声', 'moegi-recruit' ),
					'search_items'       => __( '職員の声を検索', 'moegi-recruit' ),
					'not_found'          => __( '職員の声が見つかりません', 'moegi-recruit' ),
					'not_found_in_trash' => __( 'ゴミ箱に職員の声はありません', 'moegi-recruit' ),
				),
				'public'        => true,
				'has_archive'   => 'voices',
				'rewrite'       => array(
					'slug'       => 'voices',
					'with_front' => false,
				),
				'menu_icon'     => 'dashicons-format-quote',
				'menu_position' => 7,
				'show_in_rest'  => true,
				'supports'      => array( 'title', 'editor', 'excerpt', 'custom-fields', 'page-attributes', 'revisions' ),
			),
			$paragraph( __( 'インタビューの本文を入力', 'moegi-recruit' ) )
		)
	);

	$taxonomy_args = array(
		'public'            => true,
		'hierarchical'      => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		// 絞り込みの URL のクエリ（job_type / employment）はテーマが扱う。分類の既定のクエリ変数と競合させない。
		'query_var'         => false,
	);

	// 職種は求人と職員の声で共有する。求人の詳細の「同じ職種の職員の声」は、この分類で結び付ける。
	register_taxonomy(
		'job_type',
		array( 'job', 'voice' ),
		array_merge(
			$taxonomy_args,
			array(
				'labels'  => array(
					'name'          => __( '職種', 'moegi-recruit' ),
					'singular_name' => __( '職種', 'moegi-recruit' ),
					'all_items'     => __( 'すべての職種', 'moegi-recruit' ),
					'edit_item'     => __( '職種を編集', 'moegi-recruit' ),
					'add_new_item'  => __( '職種を追加', 'moegi-recruit' ),
				),
				'rewrite' => array(
					'slug'       => 'job-type',
					'with_front' => false,
				),
			)
		)
	);

	// 雇用形態は絞り込みにのみ用いる。雇用形態ごとの一覧ページは持たない。
	register_taxonomy(
		'employment',
		'job',
		array_merge(
			$taxonomy_args,
			array(
				'publicly_queryable' => false,
				'rewrite'            => false,
				'labels'             => array(
					'name'          => __( '雇用形態', 'moegi-recruit' ),
					'singular_name' => __( '雇用形態', 'moegi-recruit' ),
					'all_items'     => __( 'すべての雇用形態', 'moegi-recruit' ),
					'edit_item'     => __( '雇用形態を編集', 'moegi-recruit' ),
					'add_new_item'  => __( '雇用形態を追加', 'moegi-recruit' ),
				),
			)
		)
	);
}
add_action( 'init', 'moegi_register_post_types' );

/**
 * 入力欄を登録する。型を定義し、REST API に公開してエディターから編集できるようにする。
 */
function moegi_register_meta() {
	$fields   = array(
		'job'      => array(
			'facility'           => array( 'integer', __( '勤務施設', 'moegi-recruit' ), 0 ),
			'wage_min'           => array( 'integer', __( '給与の下限（円）', 'moegi-recruit' ), 0 ),
			'wage_max'           => array( 'integer', __( '給与の上限（円）', 'moegi-recruit' ), 0 ),
			'bonus'              => array( 'string', __( '賞与', 'moegi-recruit' ), '' ),
			'allowances'         => array( 'textarea', __( '手当の内訳', 'moegi-recruit' ), '' ),
			'hours'              => array( 'string', __( '勤務時間', 'moegi-recruit' ), '' ),
			'night_shifts'       => array( 'integer', __( '夜勤の回数（月）', 'moegi-recruit' ), 0 ),
			'holidays'           => array( 'integer', __( '年間休日（日）', 'moegi-recruit' ), 0 ),
			'holiday_note'       => array( 'string', __( '休日・休暇', 'moegi-recruit' ), '' ),
			'qualification_note' => array( 'string', __( '資格の補足', 'moegi-recruit' ), '' ),
		),
		'facility' => array(
			'kind'        => array( 'string', __( '施設種別', 'moegi-recruit' ), '' ),
			'capacity'    => array( 'integer', __( '定員（名）', 'moegi-recruit' ), 0 ),
			'postal_code' => array( 'string', __( '郵便番号', 'moegi-recruit' ), '' ),
			'region'      => array( 'string', __( '都道府県', 'moegi-recruit' ), '' ),
			'locality'    => array( 'string', __( '市区町村', 'moegi-recruit' ), '' ),
			'street'      => array( 'string', __( '町名・番地', 'moegi-recruit' ), '' ),
			'access'      => array( 'string', __( '最寄り駅からの所要時間', 'moegi-recruit' ), '' ),
			'opened'      => array( 'integer', __( '開設年', 'moegi-recruit' ), 0 ),
			'staff'       => array( 'integer', __( '職員数', 'moegi-recruit' ), 0 ),
		),
		'voice'    => array(
			'facility' => array( 'integer', __( '勤務施設', 'moegi-recruit' ), 0 ),
			'joined'   => array( 'integer', __( '入職年', 'moegi-recruit' ), 0 ),
			'career'   => array( 'string', __( '入職前の経歴', 'moegi-recruit' ), '' ),
			'day'      => array( 'textarea', __( '1日の流れ', 'moegi-recruit' ), '' ),
		),
	);
	$sanitize = array(
		'integer'  => 'moegi_sanitize_meta_integer',
		'string'   => 'moegi_sanitize_meta_string',
		'textarea' => 'sanitize_textarea_field',
	);
	foreach ( $fields as $post_type => $list ) {
		foreach ( $list as $key => $field ) {
			list( $type, $label, $fallback ) = $field;
			register_post_meta(
				$post_type,
				$key,
				array(
					'type'              => 'textarea' === $type ? 'string' : $type,
					'label'             => $label,
					'single'            => true,
					'default'           => $fallback,
					'show_in_rest'      => true,
					'sanitize_callback' => $sanitize[ $type ],
				)
			);
		}
	}

	// 選択肢から選ぶ入力欄。選択肢にない値は既定値とする。
	$choices = array(
		'job'      => array(
			'wage_unit'     => array( __( '給与の単位', 'moegi-recruit' ), moegi_wage_unit_options(), 'monthly' ),
			'qualification' => array( __( '必要な資格', 'moegi-recruit' ), moegi_qualification_options(), 'none' ),
			'status'        => array( __( '募集状況', 'moegi-recruit' ), moegi_status_options(), 'open' ),
		),
		'facility' => array(
			'art' => array( __( 'イラスト', 'moegi-recruit' ), moegi_facility_art_options(), '' ),
		),
		'voice'    => array(
			'portrait' => array( __( 'イラスト', 'moegi-recruit' ), moegi_portrait_options(), '' ),
		),
	);
	foreach ( $choices as $post_type => $list ) {
		foreach ( $list as $key => $field ) {
			list( $label, $options, $fallback ) = $field;
			register_post_meta(
				$post_type,
				$key,
				array(
					'type'              => 'string',
					'label'             => $label,
					'single'            => true,
					'default'           => $fallback,
					'show_in_rest'      => array(
						'schema' => array( 'enum' => array_map( 'strval', array_keys( $options ) ) ),
					),
					'sanitize_callback' => static function ( $value ) use ( $options, $fallback ) {
						return array_key_exists( (string) $value, $options ) ? (string) $value : $fallback;
					},
				)
			);
		}
	}

	register_post_meta(
		'job',
		'inexperienced',
		array(
			'type'              => 'boolean',
			'label'             => __( '未経験の応募', 'moegi-recruit' ),
			'single'            => true,
			'default'           => false,
			'show_in_rest'      => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
		)
	);

	// 掲載期限は Y-m-d の文字列で保存する。文字列の大小が日付の前後と一致するため、そのまま比較できる。
	register_post_meta(
		'job',
		'deadline',
		array(
			'type'              => 'string',
			'label'             => __( '掲載期限', 'moegi-recruit' ),
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => array(
				'schema' => array( 'pattern' => '^(\d{4}-\d{2}-\d{2})?$' ),
			),
			'sanitize_callback' => 'moegi_sanitize_date',
		)
	);
}
add_action( 'init', 'moegi_register_meta' );

/**
 * 整数の入力欄を整える。負の値は 0 とする。
 *
 * @param mixed $value 入力値。
 * @return int 整えた値。
 */
function moegi_sanitize_meta_integer( $value ) {
	return max( 0, (int) $value );
}

/**
 * 文字列の入力欄を整える。
 *
 * @param mixed $value 入力値。
 * @return string 整えた値。
 */
function moegi_sanitize_meta_string( $value ) {
	return sanitize_text_field( (string) $value );
}

/**
 * 日付の入力欄を整える。実在しない日付と書式の異なる値は空文字列とする。
 *
 * @param mixed $value 入力値。
 * @return string 整えた値（Y-m-d）。
 */
function moegi_sanitize_date( $value ) {
	$value = (string) $value;
	if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m ) || ! checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ) {
		return '';
	}
	return $value;
}

/**
 * 求人・施設・職員の声のスラッグが日本語（URL エンコードされた文字列）になる場合は、job-<ID> などとする。
 *
 * 求人名などは日本語で入力されるため、既定ではスラッグが読めない長い URL になる。
 * 英数字のスラッグを入力した場合はそのまま用いる。
 *
 * @param string $slug      一意化したスラッグ。
 * @param int    $post_id   投稿 ID。
 * @param string $status    投稿の状態。
 * @param string $post_type 投稿タイプ。
 * @return string スラッグ。
 */
function moegi_readable_slug( $slug, $post_id, $status, $post_type ) {
	if ( ! in_array( $post_type, array( 'job', 'facility', 'voice' ), true ) || ! $post_id || ! str_contains( $slug, '%' ) ) {
		return $slug;
	}
	return $post_type . '-' . $post_id;
}
add_filter( 'wp_unique_post_slug', 'moegi_readable_slug', 10, 4 );
