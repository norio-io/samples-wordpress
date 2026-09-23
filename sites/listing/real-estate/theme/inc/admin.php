<?php
/**
 * 管理画面
 *
 * - 物件の一覧画面に賃料・掲載状態の列を加える（エリア・間取りは分類の列として表示する）
 * - 店舗情報の設定画面（設定 → 店舗情報）
 * - エディターの「物件情報」パネルとテーマのブロックの読み込み
 *
 * @package hinata-realty
 */

/**
 * 物件の一覧画面の列を並べ替え、賃料と掲載状態の列を加える。
 *
 * @param array $columns 列。
 * @return array 変更後の列。
 */
function hinata_property_columns( $columns ) {
	$result = array();
	foreach ( $columns as $key => $label ) {
		$result[ $key ] = $label;
		if ( 'title' === $key ) {
			$result['rent'] = __( '賃料', 'hinata-realty' );
		}
	}
	// 掲載状態は日付の直前に置く。
	$date = $result['date'] ?? null;
	unset( $result['date'] );
	$result['status'] = __( '掲載状態', 'hinata-realty' );
	if ( $date ) {
		$result['date'] = $date;
	}
	return $result;
}
add_filter( 'manage_property_posts_columns', 'hinata_property_columns' );

/**
 * 物件の一覧画面の列の値を出力する。
 *
 * @param string $column  列の名前。
 * @param int    $post_id 物件の投稿 ID。
 */
function hinata_property_column_value( $column, $post_id ) {
	if ( 'rent' === $column ) {
		$p = hinata_get_property( $post_id );
		printf(
			'<span class="hinata-col-rent">%1$s</span><br><span class="hinata-col-fee">%2$s</span>',
			esc_html( number_format( $p['rent'] ) . '円' ),
			esc_html( sprintf( '管理費 %s', hinata_format_yen( $p['fee'] ) ) )
		);
	}
	if ( 'status' === $column ) {
		$p = hinata_get_property( $post_id );
		printf(
			'<span class="hinata-col-status is-%1$s">%2$s</span>',
			esc_attr( $p['status'] ),
			esc_html( hinata_status_options()[ $p['status'] ] ?? '' )
		);
	}
}
add_action( 'manage_property_posts_custom_column', 'hinata_property_column_value', 10, 2 );

/**
 * 賃料の列で並べ替えられるようにする。
 *
 * @param array $columns 並べ替えできる列。
 * @return array 変更後の列。
 */
function hinata_property_sortable_columns( $columns ) {
	$columns['rent'] = 'rent';
	return $columns;
}
add_filter( 'manage_edit-property_sortable_columns', 'hinata_property_sortable_columns' );

/**
 * 管理画面の物件一覧で、賃料の列による並べ替えを数値として扱う。
 *
 * @param WP_Query $query クエリ。
 */
function hinata_admin_sort_by_rent( $query ) {
	if ( is_admin() && $query->is_main_query() && 'rent' === $query->get( 'orderby' ) ) {
		$query->set( 'meta_key', 'rent' ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- 並べ替えに必要なため。
		$query->set( 'orderby', 'meta_value_num' );
	}
}
add_action( 'pre_get_posts', 'hinata_admin_sort_by_rent' );

/**
 * 管理画面の列の見た目を整える。
 */
function hinata_admin_styles() {
	$screen = get_current_screen();
	if ( ! $screen || 'edit-property' !== $screen->id ) {
		return;
	}
	wp_add_inline_style(
		'list-tables',
		'.column-rent{width:9em}.column-status{width:7em}.hinata-col-rent{font-weight:600;font-variant-numeric:tabular-nums}.hinata-col-fee{color:#646970;font-size:12px}'
		. '.hinata-col-status{display:inline-block;padding:1px 8px;border-radius:3px;background:#edfaef;color:#00450c}.hinata-col-status.is-contracted{background:#f0f0f1;color:#50575e}'
	);
}
add_action( 'admin_enqueue_scripts', 'hinata_admin_styles' );

/**
 * エディターで物件を保存した後、未設定の入力欄へ既定値を保存する。
 *
 * 絞り込み・並べ替えの条件は入力欄の値で判定するため、値の行がない物件を作らない。
 * 入力欄の保存より後に実行するため、REST API の保存完了時の処理に掛ける
 * （save_post に掛けると、WXR の取り込みで入力欄より先に既定値が保存され、値が重複する）。
 *
 * @param WP_Post $post 物件。
 */
function hinata_fill_default_meta( $post ) {
	foreach ( get_registered_meta_keys( 'post', 'property' ) as $key => $args ) {
		if ( ! $args['single'] || metadata_exists( 'post', $post->ID, $key ) ) {
			continue;
		}
		update_post_meta( $post->ID, $key, $args['default'] );
	}
}
add_action( 'rest_after_insert_property', 'hinata_fill_default_meta' );

/**
 * 店舗情報の設定を登録する。REST API にも公開する。
 */
function hinata_register_store_setting() {
	$properties = array();
	foreach ( hinata_store_defaults() as $key => $value ) {
		$properties[ $key ] = array( 'type' => 'string' );
	}
	register_setting(
		'hinata_store',
		'hinata_store',
		array(
			'type'              => 'object',
			'default'           => hinata_store_defaults(),
			'sanitize_callback' => 'hinata_sanitize_store',
			'show_in_rest'      => array( 'schema' => array( 'properties' => $properties ) ),
		)
	);
}
add_action( 'init', 'hinata_register_store_setting' );

/**
 * 店舗情報を整える。
 *
 * @param mixed $value 入力値。
 * @return array<string, string> 整えた値。
 */
function hinata_sanitize_store( $value ) {
	$value  = is_array( $value ) ? $value : array();
	$result = array();
	foreach ( hinata_store_defaults() as $key => $fallback ) {
		$result[ $key ] = isset( $value[ $key ] ) ? sanitize_text_field( $value[ $key ] ) : $fallback;
	}
	return $result;
}

/**
 * 店舗情報の設定画面を追加する。
 */
function hinata_add_store_page() {
	add_options_page(
		__( '店舗情報', 'hinata-realty' ),
		__( '店舗情報', 'hinata-realty' ),
		'manage_options',
		'hinata-store',
		'hinata_render_store_page'
	);
}
add_action( 'admin_menu', 'hinata_add_store_page' );

/**
 * 店舗情報の設定画面を出力する。
 */
function hinata_render_store_page() {
	$store  = hinata_get_store();
	$labels = array(
		'address' => __( '所在地', 'hinata-realty' ),
		'tel'     => __( '電話番号', 'hinata-realty' ),
		'hours'   => __( '営業時間', 'hinata-realty' ),
		'closed'  => __( '定休日', 'hinata-realty' ),
	);
	?>
	<div class="wrap">
		<h1><?php esc_html_e( '店舗情報', 'hinata-realty' ); ?></h1>
		<p><?php esc_html_e( 'ここで変更した内容は、ヘッダー・フッター・トップ・会社概要・物件の問い合わせ導線に反映されます。', 'hinata-realty' ); ?></p>
		<form action="options.php" method="post">
			<?php settings_fields( 'hinata_store' ); ?>
			<table class="form-table" role="presentation">
				<?php foreach ( $labels as $key => $label ) : ?>
					<tr>
						<th scope="row"><label for="hinata-store-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td><input class="regular-text" type="text" id="hinata-store-<?php echo esc_attr( $key ); ?>" name="hinata_store[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $store[ $key ] ); ?>"></td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * エディターのスクリプトを読み込む。テーマのブロックと、物件の編集画面の「物件情報」パネルを登録する。
 */
function hinata_enqueue_editor_assets() {
	wp_enqueue_script(
		'hinata-realty-editor',
		get_theme_file_uri( 'assets/js/editor.js' ),
		array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-data', 'wp-core-data', 'wp-editor', 'wp-plugins', 'wp-server-side-render', 'wp-block-editor', 'wp-i18n', 'wp-dom-ready', 'wp-preferences' ),
		wp_get_theme()->get( 'Version' ),
		true
	);
	$blocks = array();
	foreach ( hinata_block_definitions() as $name => $args ) {
		$blocks[ $name ] = array(
			'title'      => $args['title'],
			'attributes' => $args['attributes'] ?? new stdClass(),
		);
	}
	wp_localize_script(
		'hinata-realty-editor',
		'hinataEditor',
		array(
			'blocks'    => $blocks,
			'equipment' => hinata_equipment_options(),
			'status'    => hinata_status_options(),
		)
	);
}
add_action( 'enqueue_block_editor_assets', 'hinata_enqueue_editor_assets' );
