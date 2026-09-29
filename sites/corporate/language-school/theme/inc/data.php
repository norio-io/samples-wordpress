<?php
/**
 * コース・講師のデータの定義と表示書式
 *
 * 選択肢（形式・レベル・募集状況・月謝の上限・並べ替え）と、月謝・費用の表示書式を1か所にまとめる。
 * 公開側・管理画面・エディターのいずれもここを参照する。
 *
 * @package hibiki-english
 */

/**
 * 形式の選択肢。キーを投稿メタ format の値として保存する。
 *
 * @return array<string, string> キーと表示名。
 */
function hibiki_format_options() {
	return array(
		'group'   => __( 'グループ', 'hibiki-english' ),
		'private' => __( 'マンツーマン', 'hibiki-english' ),
		'online'  => __( 'オンライン', 'hibiki-english' ),
	);
}

/**
 * レベルの選択肢。
 *
 * @return array<string, string> キーと表示名。
 */
function hibiki_level_options() {
	return array(
		'starter'      => __( '入門', 'hibiki-english' ),
		'elementary'   => __( '初級', 'hibiki-english' ),
		'intermediate' => __( '中級', 'hibiki-english' ),
		'advanced'     => __( '上級', 'hibiki-english' ),
	);
}

/**
 * 募集状況の選択肢。
 *
 * - open / few: 受付中。一覧・トップに表示し、体験レッスンへ案内する
 * - full:       満席。一覧に残し、導線をキャンセル待ちの案内に切り替える
 * - preparing:  開講準備中。一覧・トップ・対象別の一覧に表示しない
 *
 * @return array<string, string> キーと表示名。
 */
function hibiki_status_options() {
	return array(
		'open'      => __( '受付中', 'hibiki-english' ),
		'few'       => __( '残りわずか', 'hibiki-english' ),
		'full'      => __( '満席', 'hibiki-english' ),
		'preparing' => __( '開講準備中', 'hibiki-english' ),
	);
}

/**
 * 講師のイラストの選択肢。テーマに同梱した SVG（assets/portraits/<キー>.svg）を指す。
 *
 * @return array<string, string> キーと表示名。
 */
function hibiki_portrait_options() {
	return array(
		''      => __( 'なし', 'hibiki-english' ),
		'short' => __( 'イラスト A（短い髪）', 'hibiki-english' ),
		'bob'   => __( 'イラスト B（肩までの髪）', 'hibiki-english' ),
		'glass' => __( 'イラスト C（眼鏡）', 'hibiki-english' ),
		'curly' => __( 'イラスト D（巻き髪）', 'hibiki-english' ),
	);
}

/**
 * 月謝の上限の選択肢（円）。
 *
 * @return int[] 月謝の上限。
 */
function hibiki_fee_max_options() {
	return array( 8000, 10000, 12000, 15000, 20000 );
}

/**
 * 並べ替えの選択肢。
 *
 * @return array<string, string> キーと表示名。先頭を既定とする。
 */
function hibiki_sort_options() {
	return array(
		'recommended' => __( 'おすすめ順', 'hibiki-english' ),
		'fee'         => __( '月謝の安い順', 'hibiki-english' ),
	);
}

/**
 * 金額を桁区切りの「円」で表す。例: 12000 → 12,000円。
 *
 * @param int $yen 金額（円）。
 * @return string 表示用の文字列。
 */
function hibiki_format_yen( $yen ) {
	return number_format( (int) $yen ) . '円';
}

/**
 * 1回あたりの金額を求める。月謝を月の回数で割り、1円未満を四捨五入する。
 *
 * @param int $fee   月謝（円）。
 * @param int $times 月の回数。
 * @return int 1回あたりの金額（円）。回数が 0 の場合は 0。
 */
function hibiki_per_lesson( $fee, $times ) {
	return $times > 0 ? (int) round( $fee / $times ) : 0;
}

/**
 * コースの入力欄をまとめて取得する。
 *
 * @param int $post_id コースの投稿 ID。
 * @return array 入力欄の値と、表示に用いる値。
 */
function hibiki_get_course( $post_id ) {
	$meta   = static function ( $key ) use ( $post_id ) {
		return get_post_meta( $post_id, $key, true );
	};
	$status = (string) $meta( 'status' );
	$status = array_key_exists( $status, hibiki_status_options() ) ? $status : 'open';
	$format = (string) $meta( 'format' );
	$level  = (string) $meta( 'level' );
	$target = get_the_terms( $post_id, 'target' );
	$aim    = get_the_terms( $post_id, 'purpose' );
	$fee    = (int) $meta( 'fee' );
	$times  = (int) $meta( 'times' );

	// 担当講師は値ごとに1行で保存している（single = false）。公開中の講師に限る。
	$instructors = array();
	foreach ( (array) get_post_meta( $post_id, 'instructors', false ) as $id ) {
		$id = (int) $id;
		if ( $id && 'instructor' === get_post_type( $id ) && 'publish' === get_post_status( $id ) && ! in_array( $id, $instructors, true ) ) {
			$instructors[] = $id;
		}
	}

	return array(
		'fee'          => $fee,
		'entry_fee'    => (int) $meta( 'entry_fee' ),
		'minutes'      => (int) $meta( 'minutes' ),
		'times'        => $times,
		'per_lesson'   => hibiki_per_lesson( $fee, $times ),
		'format'       => $format,
		'format_label' => hibiki_format_options()[ $format ] ?? '',
		'capacity'     => (int) $meta( 'capacity' ),
		'schedule'     => (string) $meta( 'schedule' ),
		'level'        => $level,
		'level_label'  => hibiki_level_options()[ $level ] ?? '',
		'flow'         => array_values( array_filter( array_map( 'trim', explode( "\n", (string) $meta( 'flow' ) ) ) ) ),
		'instructors'  => $instructors,
		'status'       => $status,
		'status_label' => hibiki_status_options()[ $status ],
		'targets'      => is_array( $target ) ? $target : array(),
		'purposes'     => is_array( $aim ) ? $aim : array(),
	);
}

/**
 * 講師の入力欄をまとめて取得する。
 *
 * @param int $post_id 講師の投稿 ID。
 * @return array 入力欄の値。
 */
function hibiki_get_instructor( $post_id ) {
	$meta = static function ( $key ) use ( $post_id ) {
		return (string) get_post_meta( $post_id, $key, true );
	};
	return array(
		'language'       => $meta( 'language' ),
		'years'          => (int) $meta( 'years' ),
		'specialty'      => $meta( 'specialty' ),
		'qualifications' => $meta( 'qualifications' ),
		'portrait'       => $meta( 'portrait' ),
	);
}

/**
 * 教室情報の既定値。
 *
 * @return array<string, string> 項目と値。
 */
function hibiki_school_defaults() {
	return array(
		'address' => '〒000-0000 千景市本町2-5-1 千景ビル3階',
		'tel'     => '000-000-0000',
		'hours'   => '平日 13:00–21:30 ／ 土曜 9:30–18:00',
		'closed'  => '日曜日・祝日',
	);
}

/**
 * 教室情報の項目名。
 *
 * @return array<string, string> 項目と表示名。
 */
function hibiki_school_labels() {
	return array(
		'address' => __( '所在地', 'hibiki-english' ),
		'tel'     => __( '電話番号', 'hibiki-english' ),
		'hours'   => __( '受付時間', 'hibiki-english' ),
		'closed'  => __( '休校日', 'hibiki-english' ),
	);
}

/**
 * 教室情報を取得する。
 *
 * @return array<string, string> 項目と値。
 */
function hibiki_get_school() {
	$saved = get_option( 'hibiki_school', array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), hibiki_school_defaults() );
}

/**
 * 電話番号の tel: の URL を返す。
 *
 * @return string URL。
 */
function hibiki_tel_url() {
	return 'tel:' . preg_replace( '/[^0-9+]/', '', hibiki_get_school()['tel'] );
}
