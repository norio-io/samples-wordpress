<?php
/**
 * コース・講師（カスタム投稿タイプ）と分類・入力欄の登録
 *
 * @package hibiki-english
 */

/**
 * コース・講師の投稿タイプと、対象・目的の分類を登録する。
 */
function hibiki_register_post_types() {
	register_post_type(
		'course',
		array(
			'labels'        => array(
				'name'               => __( 'コース', 'hibiki-english' ),
				'singular_name'      => __( 'コース', 'hibiki-english' ),
				'add_new'            => __( 'コースを追加', 'hibiki-english' ),
				'add_new_item'       => __( 'コースを追加', 'hibiki-english' ),
				'edit_item'          => __( 'コースを編集', 'hibiki-english' ),
				'all_items'          => __( 'コース一覧', 'hibiki-english' ),
				'archives'           => __( 'コース一覧', 'hibiki-english' ),
				'search_items'       => __( 'コースを検索', 'hibiki-english' ),
				'not_found'          => __( 'コースが見つかりません', 'hibiki-english' ),
				'not_found_in_trash' => __( 'ゴミ箱にコースはありません', 'hibiki-english' ),
			),
			'public'        => true,
			'has_archive'   => 'courses',
			'rewrite'       => array(
				'slug'       => 'courses',
				'with_front' => false,
			),
			'menu_icon'     => 'dashicons-welcome-learn-more',
			'menu_position' => 5,
			'show_in_rest'  => true,
			// 「順序」（page-attributes）を、公開側の「おすすめ順」に用いる。
			'supports'      => array( 'title', 'editor', 'excerpt', 'custom-fields', 'page-attributes', 'revisions' ),
			// 本文は「コースの内容」の段落1つに固定する。コースのページのレイアウトはテンプレートが担い、
			// 更新担当者は入力欄と文章だけを編集するため、ブロックの追加・移動を許さない。
			'template'      => array(
				array(
					'core/paragraph',
					array( 'placeholder' => __( 'コースの内容を入力', 'hibiki-english' ) ),
				),
			),
			'template_lock' => 'insert',
		)
	);

	register_post_type(
		'instructor',
		array(
			'labels'        => array(
				'name'               => __( '講師', 'hibiki-english' ),
				'singular_name'      => __( '講師', 'hibiki-english' ),
				'add_new'            => __( '講師を追加', 'hibiki-english' ),
				'add_new_item'       => __( '講師を追加', 'hibiki-english' ),
				'edit_item'          => __( '講師を編集', 'hibiki-english' ),
				'all_items'          => __( '講師一覧', 'hibiki-english' ),
				'archives'           => __( '講師紹介', 'hibiki-english' ),
				'search_items'       => __( '講師を検索', 'hibiki-english' ),
				'not_found'          => __( '講師が見つかりません', 'hibiki-english' ),
				'not_found_in_trash' => __( 'ゴミ箱に講師はいません', 'hibiki-english' ),
			),
			'public'        => true,
			'has_archive'   => 'instructors',
			'rewrite'       => array(
				'slug'       => 'instructors',
				'with_front' => false,
			),
			'menu_icon'     => 'dashicons-groups',
			'menu_position' => 6,
			'show_in_rest'  => true,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'page-attributes', 'revisions' ),
			'template'      => array(
				array(
					'core/paragraph',
					array( 'placeholder' => __( 'プロフィールを入力', 'hibiki-english' ) ),
				),
			),
			'template_lock' => 'insert',
		)
	);

	$taxonomy_args = array(
		'hierarchical'      => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		// 絞り込みの URL のクエリ（target / purpose）はテーマが扱う。分類の既定のクエリ変数と競合させない。
		'query_var'         => false,
	);

	register_taxonomy(
		'target',
		'course',
		array_merge(
			$taxonomy_args,
			array(
				'public'  => true,
				'labels'  => array(
					'name'          => __( '対象', 'hibiki-english' ),
					'singular_name' => __( '対象', 'hibiki-english' ),
					'all_items'     => __( 'すべての対象', 'hibiki-english' ),
					'edit_item'     => __( '対象を編集', 'hibiki-english' ),
					'add_new_item'  => __( '対象を追加', 'hibiki-english' ),
				),
				'rewrite' => array(
					'slug'       => 'target',
					'with_front' => false,
				),
			)
		)
	);

	// 目的は絞り込みにのみ用いる。目的ごとの一覧ページは持たない。
	register_taxonomy(
		'purpose',
		'course',
		array_merge(
			$taxonomy_args,
			array(
				'public'             => true,
				'publicly_queryable' => false,
				'rewrite'            => false,
				'labels'             => array(
					'name'          => __( '目的', 'hibiki-english' ),
					'singular_name' => __( '目的', 'hibiki-english' ),
					'all_items'     => __( 'すべての目的', 'hibiki-english' ),
					'edit_item'     => __( '目的を編集', 'hibiki-english' ),
					'add_new_item'  => __( '目的を追加', 'hibiki-english' ),
				),
			)
		)
	);
}
add_action( 'init', 'hibiki_register_post_types' );

/**
 * コース・講師の入力欄を登録する。型を定義し、REST API に公開してエディターから編集できるようにする。
 */
function hibiki_register_meta() {
	$course = array(
		'fee'       => array( 'integer', __( '月謝（円、税込）', 'hibiki-english' ), 0 ),
		'entry_fee' => array( 'integer', __( '入会金（円、税込）', 'hibiki-english' ), 0 ),
		'minutes'   => array( 'integer', __( '1回の時間（分）', 'hibiki-english' ), 50 ),
		'times'     => array( 'integer', __( '月の回数', 'hibiki-english' ), 4 ),
		'capacity'  => array( 'integer', __( '定員（名）', 'hibiki-english' ), 1 ),
		'schedule'  => array( 'string', __( '開講曜日と時間帯', 'hibiki-english' ), '' ),
		'flow'      => array( 'string', __( '1回のレッスンの流れ', 'hibiki-english' ), '' ),
	);
	foreach ( $course as $key => $field ) {
		list( $type, $label, $fallback ) = $field;
		register_post_meta(
			'course',
			$key,
			array(
				'type'              => $type,
				'label'             => $label,
				'single'            => true,
				'default'           => $fallback,
				'show_in_rest'      => true,
				'sanitize_callback' => 'flow' === $key ? 'sanitize_textarea_field' : 'hibiki_sanitize_meta_' . $type,
			)
		);
	}

	$choices = array(
		'format' => array( __( '形式', 'hibiki-english' ), hibiki_format_options(), 'group' ),
		'level'  => array( __( 'レベル', 'hibiki-english' ), hibiki_level_options(), 'starter' ),
		'status' => array( __( '募集状況', 'hibiki-english' ), hibiki_status_options(), 'open' ),
	);
	foreach ( $choices as $key => $field ) {
		list( $label, $options, $fallback ) = $field;
		register_post_meta(
			'course',
			$key,
			array(
				'type'              => 'string',
				'label'             => $label,
				'single'            => true,
				'default'           => $fallback,
				'show_in_rest'      => array(
					'schema' => array( 'enum' => array_keys( $options ) ),
				),
				'sanitize_callback' => static function ( $value ) use ( $options, $fallback ) {
					return array_key_exists( (string) $value, $options ) ? (string) $value : $fallback;
				},
			)
		);
	}

	// 担当講師は講師の投稿 ID を値ごとに1行で保存する（single = false）。1件のコースを複数の講師が担当するため。
	// 講師の詳細の「担当コース」は、この値から逆引きする。
	register_post_meta(
		'course',
		'instructors',
		array(
			'type'              => 'integer',
			'label'             => __( '担当講師', 'hibiki-english' ),
			'single'            => false,
			'show_in_rest'      => true,
			'sanitize_callback' => 'absint',
		)
	);

	$instructor = array(
		'language'       => array( 'string', __( '担当言語', 'hibiki-english' ), '' ),
		'years'          => array( 'integer', __( '指導歴（年）', 'hibiki-english' ), 0 ),
		'specialty'      => array( 'string', __( '得意分野', 'hibiki-english' ), '' ),
		'qualifications' => array( 'string', __( '保有資格', 'hibiki-english' ), '' ),
	);
	foreach ( $instructor as $key => $field ) {
		list( $type, $label, $fallback ) = $field;
		register_post_meta(
			'instructor',
			$key,
			array(
				'type'              => $type,
				'label'             => $label,
				'single'            => true,
				'default'           => $fallback,
				'show_in_rest'      => true,
				'sanitize_callback' => 'hibiki_sanitize_meta_' . $type,
			)
		);
	}
	register_post_meta(
		'instructor',
		'portrait',
		array(
			'type'              => 'string',
			'label'             => __( 'イラスト', 'hibiki-english' ),
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => array(
				'schema' => array( 'enum' => array_keys( hibiki_portrait_options() ) ),
			),
			'sanitize_callback' => static function ( $value ) {
				return array_key_exists( (string) $value, hibiki_portrait_options() ) ? (string) $value : '';
			},
		)
	);
}
add_action( 'init', 'hibiki_register_meta' );

/**
 * 整数の入力欄を整える。負の値は 0 とする。
 *
 * @param mixed $value 入力値。
 * @return int 整えた値。
 */
function hibiki_sanitize_meta_integer( $value ) {
	return max( 0, (int) $value );
}

/**
 * 文字列の入力欄を整える。
 *
 * @param mixed $value 入力値。
 * @return string 整えた値。
 */
function hibiki_sanitize_meta_string( $value ) {
	return sanitize_text_field( (string) $value );
}

/**
 * コース・講師のスラッグが日本語（URL エンコードされた文字列）になる場合は、course-<ID> などとする。
 *
 * コース名・講師名は日本語で入力されるため、既定ではスラッグが読めない長い URL になる。
 * 英数字のスラッグを入力した場合はそのまま用いる。
 *
 * @param string $slug      一意化したスラッグ。
 * @param int    $post_id   投稿 ID。
 * @param string $status    投稿の状態。
 * @param string $post_type 投稿タイプ。
 * @return string スラッグ。
 */
function hibiki_readable_slug( $slug, $post_id, $status, $post_type ) {
	if ( ! in_array( $post_type, array( 'course', 'instructor' ), true ) || ! $post_id || ! str_contains( $slug, '%' ) ) {
		return $slug;
	}
	return $post_type . '-' . $post_id;
}
add_filter( 'wp_unique_post_slug', 'hibiki_readable_slug', 10, 4 );
