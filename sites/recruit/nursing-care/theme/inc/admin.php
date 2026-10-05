<?php
/**
 * 管理画面
 *
 * - 求人の一覧画面に勤務施設・給与・募集状況・掲載期限の列を加える（職種・雇用形態は分類の列として表示する）
 * - 採用窓口の設定画面（設定 → 採用窓口）
 * - エディターの「求人情報」「施設情報」「職員の情報」パネルとテーマのブロックの読み込み
 *
 * @package moegi-recruit
 */

/**
 * 求人の一覧画面の列を並べ替え、勤務施設・給与・募集状況・掲載期限の列を加える。
 *
 * 求人名の後ろに職種・勤務施設・雇用形態・給与・募集状況・掲載期限の順に置き、日付を最後に置く。
 *
 * @param array $columns 列。
 * @return array 変更後の列。
 */
function moegi_job_columns( $columns ) {
	$type   = $columns['taxonomy-job_type'] ?? null;
	$employ = $columns['taxonomy-employment'] ?? null;
	$date   = $columns['date'] ?? null;
	unset( $columns['taxonomy-job_type'], $columns['taxonomy-employment'], $columns['date'] );

	$result = array();
	foreach ( $columns as $key => $label ) {
		$result[ $key ] = $label;
		if ( 'title' !== $key ) {
			continue;
		}
		if ( $type ) {
			$result['taxonomy-job_type'] = $type;
		}
		$result['facility'] = __( '勤務施設', 'moegi-recruit' );
		if ( $employ ) {
			$result['taxonomy-employment'] = $employ;
		}
		$result['wage']     = __( '給与', 'moegi-recruit' );
		$result['status']   = __( '募集状況', 'moegi-recruit' );
		$result['deadline'] = __( '掲載期限', 'moegi-recruit' );
	}
	if ( $date ) {
		$result['date'] = $date;
	}
	return $result;
}
add_filter( 'manage_job_posts_columns', 'moegi_job_columns' );

/**
 * 求人の一覧画面の列の値を出力する。
 *
 * 掲載期限を過ぎた求人は、募集状況の列に「期限切れ」と表示する。
 *
 * @param string $column  列の名前。
 * @param int    $post_id 求人の投稿 ID。
 */
function moegi_job_column_value( $column, $post_id ) {
	if ( ! in_array( $column, array( 'facility', 'wage', 'status', 'deadline' ), true ) ) {
		return;
	}
	$j = moegi_get_job( $post_id );
	if ( 'facility' === $column ) {
		echo esc_html( $j['facility'] ? get_the_title( $j['facility'] ) : '—' );
	}
	if ( 'wage' === $column ) {
		printf(
			'<span class="moegi-col-unit">%1$s</span><br><span class="moegi-col-wage">%2$s</span>',
			esc_html( $j['wage_unit_label'] ),
			esc_html( moegi_wage_range( $j['wage_min'], $j['wage_max'] ) . '円' )
		);
	}
	if ( 'status' === $column ) {
		printf( '<span class="moegi-col-status is-%1$s">%2$s</span>', esc_attr( $j['state'] ), esc_html( $j['state_label'] ) );
	}
	if ( 'deadline' === $column ) {
		printf(
			'<span class="moegi-col-deadline%1$s">%2$s</span>',
			$j['expired'] ? ' is-expired' : '',
			esc_html( $j['deadline'] ? str_replace( '-', '.', $j['deadline'] ) : __( '期限なし', 'moegi-recruit' ) )
		);
	}
}
add_action( 'manage_job_posts_custom_column', 'moegi_job_column_value', 10, 2 );

/**
 * 掲載期限の列で並べ替えられるようにする。
 *
 * @param array $columns 並べ替えできる列。
 * @return array 変更後の列。
 */
function moegi_job_sortable_columns( $columns ) {
	$columns['deadline'] = 'deadline';
	return $columns;
}
add_filter( 'manage_edit-job_sortable_columns', 'moegi_job_sortable_columns' );

/**
 * 管理画面の求人一覧で、掲載期限の列による並べ替えを入力欄の値で行う。
 *
 * @param WP_Query $query クエリ。
 */
function moegi_admin_job_order( $query ) {
	if ( is_admin() && $query->is_main_query() && 'job' === $query->get( 'post_type' ) && 'deadline' === $query->get( 'orderby' ) ) {
		$query->set( 'meta_key', 'deadline' ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- 並べ替えに必要なため。
		$query->set( 'orderby', 'meta_value' );
	}
}
add_action( 'pre_get_posts', 'moegi_admin_job_order' );

/**
 * 管理画面の列の見た目を整える。
 */
function moegi_admin_styles() {
	$screen = get_current_screen();
	if ( ! $screen || 'edit-job' !== $screen->id ) {
		return;
	}
	wp_add_inline_style(
		'list-tables',
		'.column-facility{width:11em}.column-wage{width:9em}.column-status{width:7em}.column-deadline{width:7em}'
		. '.moegi-col-unit{color:#646970;font-size:12px}.moegi-col-wage,.moegi-col-deadline{font-variant-numeric:tabular-nums}.moegi-col-wage{font-weight:600}'
		. '.moegi-col-status{display:inline-block;padding:1px 8px;border-radius:3px;background:#edfaef;color:#00450c}'
		. '.moegi-col-status.is-urgent{background:#efc64a;color:#1f2a22;font-weight:600}.moegi-col-status.is-closed{background:#f0f0f1;color:#50575e}'
		. '.moegi-col-status.is-expired{background:#fcf0f1;color:#8a2424}.moegi-col-deadline.is-expired{color:#8a2424}'
	);
}
add_action( 'admin_enqueue_scripts', 'moegi_admin_styles' );

/**
 * エディターで保存した後、未設定の入力欄へ既定値を保存する。
 *
 * 絞り込み・並べ替えの条件は入力欄の値で判定するため、値の行がない投稿を作らない。
 * 入力欄の保存より後に実行するため、REST API の保存完了時の処理に掛ける
 * （save_post に掛けると、WXR の取り込みで入力欄より先に既定値が保存され、値が重複する）。
 *
 * @param WP_Post $post 求人・施設・職員の声。
 */
function moegi_fill_default_meta( $post ) {
	foreach ( get_registered_meta_keys( 'post', $post->post_type ) as $key => $args ) {
		if ( ! $args['single'] || metadata_exists( 'post', $post->ID, $key ) ) {
			continue;
		}
		update_post_meta( $post->ID, $key, $args['default'] );
	}
}
add_action( 'rest_after_insert_job', 'moegi_fill_default_meta' );
add_action( 'rest_after_insert_facility', 'moegi_fill_default_meta' );
add_action( 'rest_after_insert_voice', 'moegi_fill_default_meta' );

/**
 * 採用窓口の設定を登録する。REST API にも公開する。
 */
function moegi_register_contact_setting() {
	$properties = array();
	foreach ( moegi_contact_defaults() as $key => $value ) {
		$properties[ $key ] = array( 'type' => 'string' );
	}
	register_setting(
		'moegi_contact',
		'moegi_contact',
		array(
			'type'              => 'object',
			'default'           => moegi_contact_defaults(),
			'sanitize_callback' => 'moegi_sanitize_contact',
			'show_in_rest'      => array( 'schema' => array( 'properties' => $properties ) ),
		)
	);
}
add_action( 'init', 'moegi_register_contact_setting' );

/**
 * 採用窓口の情報を整える。見学会の日程は複数行とする。
 *
 * @param mixed $value 入力値。
 * @return array<string, string> 整えた値。
 */
function moegi_sanitize_contact( $value ) {
	$value  = is_array( $value ) ? $value : array();
	$result = array();
	foreach ( moegi_contact_defaults() as $key => $fallback ) {
		if ( ! isset( $value[ $key ] ) ) {
			$result[ $key ] = $fallback;
			continue;
		}
		$result[ $key ] = 'tour' === $key ? sanitize_textarea_field( $value[ $key ] ) : sanitize_text_field( $value[ $key ] );
	}
	return $result;
}

/**
 * 採用窓口の設定画面を追加する。
 */
function moegi_add_contact_page() {
	add_options_page(
		__( '採用窓口', 'moegi-recruit' ),
		__( '採用窓口', 'moegi-recruit' ),
		'manage_options',
		'moegi-contact',
		'moegi_render_contact_page'
	);
}
add_action( 'admin_menu', 'moegi_add_contact_page' );

/**
 * 採用窓口の設定画面を出力する。
 */
function moegi_render_contact_page() {
	$contact = moegi_get_contact();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( '採用窓口', 'moegi-recruit' ); ?></h1>
		<p><?php esc_html_e( 'ここで変更した内容は、ヘッダー・フッター・トップ・求人の詳細・応募フォームのページに反映されます。', 'moegi-recruit' ); ?></p>
		<form action="options.php" method="post">
			<?php settings_fields( 'moegi_contact' ); ?>
			<table class="form-table" role="presentation">
				<?php foreach ( moegi_contact_labels() as $key => $label ) : ?>
					<tr>
						<th scope="row"><label for="moegi-contact-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td>
							<?php if ( 'tour' === $key ) : ?>
								<textarea class="large-text" rows="4" id="moegi-contact-tour" name="moegi_contact[tour]" aria-describedby="moegi-contact-tour-help"><?php echo esc_textarea( $contact['tour'] ); ?></textarea>
								<p class="description" id="moegi-contact-tour-help"><?php esc_html_e( '1行に1回分の日程を書きます。例: 11月8日（土）10:00– もえぎの里', 'moegi-recruit' ); ?></p>
							<?php else : ?>
								<input class="regular-text" type="text" id="moegi-contact-<?php echo esc_attr( $key ); ?>" name="moegi_contact[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $contact[ $key ] ); ?>">
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * エディターのスクリプトを読み込む。テーマのブロックと、求人・施設・職員の声の編集画面の入力パネルを登録する。
 */
function moegi_enqueue_editor_assets() {
	wp_enqueue_script(
		'moegi-recruit-editor',
		get_theme_file_uri( 'assets/js/editor.js' ),
		array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-data', 'wp-core-data', 'wp-editor', 'wp-plugins', 'wp-server-side-render', 'wp-block-editor', 'wp-i18n', 'wp-dom-ready', 'wp-preferences' ),
		wp_get_theme()->get( 'Version' ),
		true
	);
	$blocks = array();
	foreach ( moegi_block_definitions() as $name => $args ) {
		$blocks[ $name ] = array(
			'title'       => $args['title'],
			'attributes'  => $args['attributes'] ?? new stdClass(),
			'usesContext' => $args['uses_context'] ?? array(),
		);
	}
	wp_localize_script(
		'moegi-recruit-editor',
		'moegiEditor',
		array(
			'blocks'        => $blocks,
			'wageUnit'      => moegi_wage_unit_options(),
			'qualification' => moegi_qualification_options(),
			'status'        => moegi_status_options(),
			'portrait'      => moegi_portrait_options(),
			'art'           => moegi_facility_art_options(),
			'today'         => moegi_today(),
		)
	);
}
add_action( 'enqueue_block_editor_assets', 'moegi_enqueue_editor_assets' );
