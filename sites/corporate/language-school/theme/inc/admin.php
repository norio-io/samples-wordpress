<?php
/**
 * 管理画面
 *
 * - コースの一覧画面に形式・月謝・募集状況の列を加える（対象・目的は分類の列として表示する）
 * - 教室情報の設定画面（設定 → 教室情報）
 * - エディターの「コース情報」「講師情報」パネルとテーマのブロックの読み込み
 *
 * @package hibiki-english
 */

/**
 * コースの一覧画面の列を並べ替え、形式・月謝・募集状況の列を加える。
 *
 * 対象の列（分類の列）をコース名の直後に移し、その後ろに形式・月謝を置く。募集状況は日付の直前に置く。
 *
 * @param array $columns 列。
 * @return array 変更後の列。
 */
function hibiki_course_columns( $columns ) {
	$target = $columns['taxonomy-target'] ?? null;
	unset( $columns['taxonomy-target'] );
	$result = array();
	foreach ( $columns as $key => $label ) {
		$result[ $key ] = $label;
		if ( 'title' === $key ) {
			if ( $target ) {
				$result['taxonomy-target'] = $target;
			}
			$result['format'] = __( '形式', 'hibiki-english' );
			$result['fee']    = __( '月謝', 'hibiki-english' );
		}
	}
	$date = $result['date'] ?? null;
	unset( $result['date'] );
	$result['status'] = __( '募集状況', 'hibiki-english' );
	if ( $date ) {
		$result['date'] = $date;
	}
	return $result;
}
add_filter( 'manage_course_posts_columns', 'hibiki_course_columns' );

/**
 * コースの一覧画面の列の値を出力する。
 *
 * @param string $column  列の名前。
 * @param int    $post_id コースの投稿 ID。
 */
function hibiki_course_column_value( $column, $post_id ) {
	if ( ! in_array( $column, array( 'format', 'fee', 'status' ), true ) ) {
		return;
	}
	$c = hibiki_get_course( $post_id );
	if ( 'format' === $column ) {
		echo esc_html( $c['format_label'] );
	}
	if ( 'fee' === $column ) {
		printf(
			'<span class="hibiki-col-fee">%1$s</span><br><span class="hibiki-col-sub">%2$s</span>',
			esc_html( hibiki_format_yen( $c['fee'] ) ),
			/* translators: %s: 1回あたりの金額 */
			esc_html( sprintf( __( '1回 %s', 'hibiki-english' ), hibiki_format_yen( $c['per_lesson'] ) ) )
		);
	}
	if ( 'status' === $column ) {
		printf( '<span class="hibiki-col-status is-%1$s">%2$s</span>', esc_attr( $c['status'] ), esc_html( $c['status_label'] ) );
	}
}
add_action( 'manage_course_posts_custom_column', 'hibiki_course_column_value', 10, 2 );

/**
 * 月謝の列で並べ替えられるようにする。
 *
 * @param array $columns 並べ替えできる列。
 * @return array 変更後の列。
 */
function hibiki_course_sortable_columns( $columns ) {
	$columns['fee'] = 'fee';
	return $columns;
}
add_filter( 'manage_edit-course_sortable_columns', 'hibiki_course_sortable_columns' );

/**
 * 管理画面のコース一覧で、月謝の列による並べ替えを数値として扱う。既定の並びは「順序」（おすすめ順）とする。
 *
 * @param WP_Query $query クエリ。
 */
function hibiki_admin_course_order( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || 'course' !== $query->get( 'post_type' ) ) {
		return;
	}
	if ( 'fee' === $query->get( 'orderby' ) ) {
		$query->set( 'meta_key', 'fee' ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- 並べ替えに必要なため。
		$query->set( 'orderby', 'meta_value_num' );
	} elseif ( ! $query->get( 'orderby' ) ) {
		$query->set(
			'orderby',
			array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			)
		);
	}
}
add_action( 'pre_get_posts', 'hibiki_admin_course_order' );

/**
 * 管理画面の列の見た目を整える。
 */
function hibiki_admin_styles() {
	$screen = get_current_screen();
	if ( ! $screen || 'edit-course' !== $screen->id ) {
		return;
	}
	wp_add_inline_style(
		'list-tables',
		'.column-format{width:8em}.column-fee{width:8em}.column-status{width:8em}.hibiki-col-fee{font-weight:600;font-variant-numeric:tabular-nums}.hibiki-col-sub{color:#646970;font-size:12px}'
		. '.hibiki-col-status{display:inline-block;padding:1px 8px;border-radius:3px;background:#edfaef;color:#00450c}'
		. '.hibiki-col-status.is-few{background:#fcf0e3;color:#6b3a00}.hibiki-col-status.is-full{background:#1d2327;color:#fff}.hibiki-col-status.is-preparing{background:#f0f0f1;color:#50575e}'
	);
}
add_action( 'admin_enqueue_scripts', 'hibiki_admin_styles' );

/**
 * エディターでコース・講師を保存した後、未設定の入力欄へ既定値を保存する。
 *
 * 絞り込み・並べ替えの条件は入力欄の値で判定するため、値の行がないコースを作らない。
 * 入力欄の保存より後に実行するため、REST API の保存完了時の処理に掛ける
 * （save_post に掛けると、WXR の取り込みで入力欄より先に既定値が保存され、値が重複する）。
 *
 * @param WP_Post $post コースまたは講師。
 */
function hibiki_fill_default_meta( $post ) {
	foreach ( get_registered_meta_keys( 'post', $post->post_type ) as $key => $args ) {
		if ( ! $args['single'] || metadata_exists( 'post', $post->ID, $key ) ) {
			continue;
		}
		update_post_meta( $post->ID, $key, $args['default'] );
	}
}
add_action( 'rest_after_insert_course', 'hibiki_fill_default_meta' );
add_action( 'rest_after_insert_instructor', 'hibiki_fill_default_meta' );

/**
 * 教室情報の設定を登録する。REST API にも公開する。
 */
function hibiki_register_school_setting() {
	$properties = array();
	foreach ( hibiki_school_defaults() as $key => $value ) {
		$properties[ $key ] = array( 'type' => 'string' );
	}
	register_setting(
		'hibiki_school',
		'hibiki_school',
		array(
			'type'              => 'object',
			'default'           => hibiki_school_defaults(),
			'sanitize_callback' => 'hibiki_sanitize_school',
			'show_in_rest'      => array( 'schema' => array( 'properties' => $properties ) ),
		)
	);
}
add_action( 'init', 'hibiki_register_school_setting' );

/**
 * 教室情報を整える。
 *
 * @param mixed $value 入力値。
 * @return array<string, string> 整えた値。
 */
function hibiki_sanitize_school( $value ) {
	$value  = is_array( $value ) ? $value : array();
	$result = array();
	foreach ( hibiki_school_defaults() as $key => $fallback ) {
		$result[ $key ] = isset( $value[ $key ] ) ? sanitize_text_field( $value[ $key ] ) : $fallback;
	}
	return $result;
}

/**
 * 教室情報の設定画面を追加する。
 */
function hibiki_add_school_page() {
	add_options_page(
		__( '教室情報', 'hibiki-english' ),
		__( '教室情報', 'hibiki-english' ),
		'manage_options',
		'hibiki-school',
		'hibiki_render_school_page'
	);
}
add_action( 'admin_menu', 'hibiki_add_school_page' );

/**
 * 教室情報の設定画面を出力する。
 */
function hibiki_render_school_page() {
	$school = hibiki_get_school();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( '教室情報', 'hibiki-english' ); ?></h1>
		<p><?php esc_html_e( 'ここで変更した内容は、ヘッダー・フッター・トップ・スクール紹介・体験レッスンのページと、コースの申し込み導線に反映されます。', 'hibiki-english' ); ?></p>
		<form action="options.php" method="post">
			<?php settings_fields( 'hibiki_school' ); ?>
			<table class="form-table" role="presentation">
				<?php foreach ( hibiki_school_labels() as $key => $label ) : ?>
					<tr>
						<th scope="row"><label for="hibiki-school-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td><input class="regular-text" type="text" id="hibiki-school-<?php echo esc_attr( $key ); ?>" name="hibiki_school[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $school[ $key ] ); ?>"></td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * エディターのスクリプトを読み込む。テーマのブロックと、コース・講師の編集画面の入力パネルを登録する。
 */
function hibiki_enqueue_editor_assets() {
	wp_enqueue_script(
		'hibiki-english-editor',
		get_theme_file_uri( 'assets/js/editor.js' ),
		array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-data', 'wp-core-data', 'wp-editor', 'wp-plugins', 'wp-server-side-render', 'wp-block-editor', 'wp-i18n', 'wp-dom-ready', 'wp-preferences' ),
		wp_get_theme()->get( 'Version' ),
		true
	);
	$blocks = array();
	foreach ( hibiki_block_definitions() as $name => $args ) {
		$blocks[ $name ] = array(
			'title'       => $args['title'],
			'attributes'  => $args['attributes'] ?? new stdClass(),
			'usesContext' => $args['uses_context'] ?? array(),
		);
	}
	wp_localize_script(
		'hibiki-english-editor',
		'hibikiEditor',
		array(
			'blocks'   => $blocks,
			'format'   => hibiki_format_options(),
			'level'    => hibiki_level_options(),
			'status'   => hibiki_status_options(),
			'portrait' => hibiki_portrait_options(),
		)
	);
}
add_action( 'enqueue_block_editor_assets', 'hibiki_enqueue_editor_assets' );
