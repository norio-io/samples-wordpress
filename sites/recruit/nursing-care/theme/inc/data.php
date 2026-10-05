<?php
/**
 * 求人・施設・職員の声のデータの定義と表示書式
 *
 * 選択肢（給与の単位・必要な資格・募集状況・並べ替えなど）と、給与・年間休日・夜勤の回数などの表示書式、
 * 募集中かどうかの判定（募集状況と掲載期限）を1か所にまとめる。公開側・管理画面・エディター・構造化データの
 * いずれもここを参照する。
 *
 * @package moegi-recruit
 */

/**
 * 給与の単位の選択肢。
 *
 * @return array<string, string> キーと表示名。
 */
function moegi_wage_unit_options() {
	return array(
		'monthly' => __( '月給', 'moegi-recruit' ),
		'hourly'  => __( '時給', 'moegi-recruit' ),
	);
}

/**
 * 必要な資格の選択肢。
 *
 * @return array<string, string> キーと表示名。
 */
function moegi_qualification_options() {
	return array(
		'none'    => __( '資格不問', 'moegi-recruit' ),
		'initial' => __( '介護職員初任者研修', 'moegi-recruit' ),
		'kaigo'   => __( '介護福祉士', 'moegi-recruit' ),
		'nurse'   => __( '看護師', 'moegi-recruit' ),
		'other'   => __( 'その他', 'moegi-recruit' ),
	);
}

/**
 * 募集状況の選択肢。
 *
 * - open:   募集中。一覧・トップ・職種別の一覧・施設の詳細に表示する
 * - urgent: 急募。募集中と同じく表示し、「急募」の札を付けて他の求人より前に並べる
 * - closed: 募集停止。一覧などに表示せず、詳細には募集を停止している旨を表示する
 *
 * 掲載期限を過ぎた求人は、募集状況にかかわらず募集停止と同じ扱いとする（moegi_job_is_open()）。
 *
 * @return array<string, string> キーと表示名。
 */
function moegi_status_options() {
	return array(
		'open'   => __( '募集中', 'moegi-recruit' ),
		'urgent' => __( '急募', 'moegi-recruit' ),
		'closed' => __( '募集停止', 'moegi-recruit' ),
	);
}

/**
 * 夜勤の有無の選択肢（絞り込み）。
 *
 * @return array<string, string> キーと表示名。
 */
function moegi_night_options() {
	return array(
		'yes' => __( '夜勤あり', 'moegi-recruit' ),
		'no'  => __( '夜勤なし', 'moegi-recruit' ),
	);
}

/**
 * 並べ替えの選択肢。いずれの並びでも、急募の求人を先に置く。
 *
 * @return array<string, string> キーと表示名。先頭を既定とする。
 */
function moegi_sort_options() {
	return array(
		'new'  => __( '新着順', 'moegi-recruit' ),
		'wage' => __( '給与の高い順', 'moegi-recruit' ),
	);
}

/**
 * 職員のイラストの選択肢。テーマに同梱した SVG（assets/portraits/<キー>.svg）を指す。
 *
 * @return array<string, string> キーと表示名。
 */
function moegi_portrait_options() {
	return array(
		''      => __( 'なし', 'moegi-recruit' ),
		'short' => __( 'イラスト A（短い髪）', 'moegi-recruit' ),
		'tied'  => __( 'イラスト B（結んだ髪）', 'moegi-recruit' ),
		'glass' => __( 'イラスト C（眼鏡）', 'moegi-recruit' ),
		'cap'   => __( 'イラスト D（帽子）', 'moegi-recruit' ),
	);
}

/**
 * 施設のイラストの選択肢。テーマに同梱した SVG（assets/facilities/<キー>.svg）を指す。
 *
 * @return array<string, string> キーと表示名。
 */
function moegi_facility_art_options() {
	return array(
		''      => __( 'なし', 'moegi-recruit' ),
		'large' => __( 'イラスト A（入所施設）', 'moegi-recruit' ),
		'day'   => __( 'イラスト B（通所施設）', 'moegi-recruit' ),
		'house' => __( 'イラスト C（グループホーム）', 'moegi-recruit' ),
	);
}

/**
 * 雇用形態の分類のスラッグと、schema.org の employmentType の対応。
 *
 * 表にないスラッグの雇用形態（管理画面で追加したもの）は OTHER とする。
 *
 * @return array<string, string[]> スラッグと employmentType。
 */
function moegi_employment_schema_types() {
	return array(
		'full-time' => array( 'FULL_TIME' ),
		'contract'  => array( 'FULL_TIME', 'TEMPORARY' ),
		'part-time' => array( 'PART_TIME' ),
	);
}

/**
 * 金額を桁区切りで表す。例: 218000 → 218,000。
 *
 * @param int $yen 金額（円）。
 * @return string 表示用の文字列。
 */
function moegi_number( $yen ) {
	return number_format( (int) $yen );
}

/**
 * 給与の金額の範囲を表す。単位の表記（月給・時給）と「円」は含まない。
 *
 * - 下限と上限がある: 218,000〜265,000
 * - 上限がない、または下限と同じ: 218,000
 * - 下限がなく上限がある: 〜265,000
 *
 * @param int $min 下限（円）。
 * @param int $max 上限（円）。
 * @return string 表示用の文字列。
 */
function moegi_wage_range( $min, $max ) {
	$min = (int) $min;
	$max = (int) $max;
	if ( $max > $min && $min > 0 ) {
		return moegi_number( $min ) . '〜' . moegi_number( $max );
	}
	if ( $max > 0 && 0 === $min ) {
		return '〜' . moegi_number( $max );
	}
	return moegi_number( $min );
}

/**
 * 給与を1行で表す。例: 月給 218,000〜265,000円、時給 1,150円。
 *
 * @param array $j 求人の値（moegi_get_job() の戻り値）。
 * @return string 表示用の文字列。
 */
function moegi_format_wage( $j ) {
	return sprintf( '%1$s %2$s円', $j['wage_unit_label'], moegi_wage_range( $j['wage_min'], $j['wage_max'] ) );
}

/**
 * 夜勤の回数を表す。
 *
 * @param int $count 夜勤の回数（月）。
 * @return string 表示用の文字列。例: 月4回、なし。
 */
function moegi_format_night( $count ) {
	/* translators: %d: 夜勤の回数 */
	return $count > 0 ? sprintf( __( '月%d回', 'moegi-recruit' ), $count ) : __( 'なし', 'moegi-recruit' );
}

/**
 * 年間休日を表す。0 はシフト制のパートなどで日数を定めない場合とする。
 *
 * @param int $days 年間休日（日）。
 * @return string 表示用の文字列。例: 115日、勤務日数による。
 */
function moegi_format_holidays( $days ) {
	/* translators: %d: 年間休日 */
	return $days > 0 ? sprintf( __( '%d日', 'moegi-recruit' ), $days ) : __( '勤務日数による', 'moegi-recruit' );
}

/**
 * 日付（Y-m-d）を「2026年12月31日」の形で表す。
 *
 * @param string $date 日付。
 * @return string 表示用の文字列。書式が不正な場合は空文字列。
 */
function moegi_format_date( $date ) {
	$d = DateTimeImmutable::createFromFormat( '!Y-m-d', (string) $date, wp_timezone() );
	return $d ? wp_date( 'Y年n月j日', $d->getTimestamp() ) : '';
}

/**
 * サイトの時刻での今日の日付（Y-m-d）。掲載期限の判定に用いる。
 *
 * @return string 今日の日付。
 */
function moegi_today() {
	return current_datetime()->format( 'Y-m-d' );
}

/**
 * 掲載期限を過ぎているか。掲載期限の当日は期限内とする。期限が未設定の求人は過ぎていないものとする。
 *
 * @param string $deadline 掲載期限（Y-m-d）。
 * @return bool 過ぎている場合は true。
 */
function moegi_is_expired( $deadline ) {
	return '' !== (string) $deadline && (string) $deadline < moegi_today();
}

/**
 * 求人の入力欄をまとめて取得する。
 *
 * @param int $post_id 求人の投稿 ID。
 * @return array 入力欄の値と、表示に用いる値。
 */
function moegi_get_job( $post_id ) {
	$meta = static function ( $key ) use ( $post_id ) {
		return get_post_meta( $post_id, $key, true );
	};

	$status   = (string) $meta( 'status' );
	$status   = array_key_exists( $status, moegi_status_options() ) ? $status : 'open';
	$deadline = (string) $meta( 'deadline' );
	$expired  = moegi_is_expired( $deadline );
	$unit     = (string) $meta( 'wage_unit' );
	$unit     = array_key_exists( $unit, moegi_wage_unit_options() ) ? $unit : 'monthly';
	$qual     = (string) $meta( 'qualification' );
	$qual     = array_key_exists( $qual, moegi_qualification_options() ) ? $qual : 'none';
	$facility = (int) $meta( 'facility' );
	$facility = $facility && 'facility' === get_post_type( $facility ) && 'publish' === get_post_status( $facility ) ? $facility : 0;
	$types    = get_the_terms( $post_id, 'job_type' );
	$employ   = get_the_terms( $post_id, 'employment' );

	// 掲載期限を過ぎた求人は、募集状況の値にかかわらず募集停止と同じ扱いとする。
	$open = ! $expired && 'closed' !== $status;

	return array(
		'facility'            => $facility,
		'wage_unit'           => $unit,
		'wage_unit_label'     => moegi_wage_unit_options()[ $unit ],
		'wage_min'            => (int) $meta( 'wage_min' ),
		'wage_max'            => (int) $meta( 'wage_max' ),
		'bonus'               => (string) $meta( 'bonus' ),
		'allowances'          => moegi_lines( (string) $meta( 'allowances' ) ),
		'hours'               => (string) $meta( 'hours' ),
		'night_shifts'        => (int) $meta( 'night_shifts' ),
		'holidays'            => (int) $meta( 'holidays' ),
		'holiday_note'        => (string) $meta( 'holiday_note' ),
		'qualification'       => $qual,
		'qualification_label' => moegi_qualification_options()[ $qual ],
		'qualification_note'  => (string) $meta( 'qualification_note' ),
		'inexperienced'       => (bool) $meta( 'inexperienced' ),
		'status'              => $status,
		'deadline'            => $deadline,
		'expired'             => $expired,
		'open'                => $open,
		'urgent'              => $open && 'urgent' === $status,
		// 表示に用いる状態。期限切れは募集状況の値より優先する。
		'state'               => $expired ? 'expired' : $status,
		'state_label'         => $expired ? __( '期限切れ', 'moegi-recruit' ) : moegi_status_options()[ $status ],
		'job_types'           => is_array( $types ) ? $types : array(),
		'employments'         => is_array( $employ ) ? $employ : array(),
	);
}

/**
 * 求人の「必要な資格」を表す。その他の場合は補足を表示し、補足があれば資格名に添える。
 *
 * @param array $j 求人の値。
 * @return string 表示用の文字列。
 */
function moegi_format_qualification( $j ) {
	if ( 'other' === $j['qualification'] ) {
		return $j['qualification_note'] ? $j['qualification_note'] : $j['qualification_label'];
	}
	return $j['qualification_note'] ? sprintf( '%1$s（%2$s）', $j['qualification_label'], $j['qualification_note'] ) : $j['qualification_label'];
}

/**
 * 施設の入力欄をまとめて取得する。
 *
 * @param int $post_id 施設の投稿 ID。
 * @return array 入力欄の値。
 */
function moegi_get_facility( $post_id ) {
	$meta  = static function ( $key ) use ( $post_id ) {
		return get_post_meta( $post_id, $key, true );
	};
	$parts = array(
		'postal_code' => (string) $meta( 'postal_code' ),
		'region'      => (string) $meta( 'region' ),
		'locality'    => (string) $meta( 'locality' ),
		'street'      => (string) $meta( 'street' ),
	);
	return array_merge(
		$parts,
		array(
			'kind'     => (string) $meta( 'kind' ),
			'capacity' => (int) $meta( 'capacity' ),
			'access'   => (string) $meta( 'access' ),
			'opened'   => (int) $meta( 'opened' ),
			'staff'    => (int) $meta( 'staff' ),
			'art'      => (string) $meta( 'art' ),
			'address'  => trim( ( $parts['postal_code'] ? '〒' . $parts['postal_code'] . ' ' : '' ) . $parts['region'] . $parts['locality'] . $parts['street'] ),
		)
	);
}

/**
 * 職員の声の入力欄をまとめて取得する。
 *
 * 1日の流れは「7:00 出勤、申し送り」の形で1行に1つ書く。先頭の時刻と内容に分ける。
 *
 * @param int $post_id 職員の声の投稿 ID。
 * @return array 入力欄の値。
 */
function moegi_get_voice( $post_id ) {
	$meta     = static function ( $key ) use ( $post_id ) {
		return get_post_meta( $post_id, $key, true );
	};
	$facility = (int) $meta( 'facility' );
	$types    = get_the_terms( $post_id, 'job_type' );
	$day      = array();
	foreach ( moegi_lines( (string) $meta( 'day' ) ) as $line ) {
		if ( preg_match( '/^(\d{1,2}:\d{2})\s*(.+)$/u', $line, $m ) ) {
			$day[] = array( $m[1], $m[2] );
		} else {
			$day[] = array( '', $line );
		}
	}
	return array(
		'facility'  => $facility && 'facility' === get_post_type( $facility ) && 'publish' === get_post_status( $facility ) ? $facility : 0,
		'joined'    => (int) $meta( 'joined' ),
		'career'    => (string) $meta( 'career' ),
		'portrait'  => (string) $meta( 'portrait' ),
		'day'       => $day,
		'job_types' => is_array( $types ) ? $types : array(),
	);
}

/**
 * 複数行の入力欄を、空行を除いた行の配列にする。
 *
 * @param string $text 入力欄の値。
 * @return string[] 行。
 */
function moegi_lines( $text ) {
	return array_values( array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", '', $text ) ) ) ) );
}

/**
 * 採用窓口の既定値。
 *
 * @return array<string, string> 項目と値。
 */
function moegi_contact_defaults() {
	return array(
		'department' => '法人本部 採用担当',
		'tel'        => '000-000-0000',
		'hours'      => '平日 9:00–17:30',
		'tour'       => "11月8日（土）10:00– 特別養護老人ホーム もえぎの里\n11月19日（水）14:00– デイサービスセンター もえぎ\n11月29日（土）10:00– グループホーム もえぎの家",
	);
}

/**
 * 採用窓口の項目名。
 *
 * @return array<string, string> 項目と表示名。
 */
function moegi_contact_labels() {
	return array(
		'department' => __( '担当部署', 'moegi-recruit' ),
		'tel'        => __( '電話番号', 'moegi-recruit' ),
		'hours'      => __( '受付時間', 'moegi-recruit' ),
		'tour'       => __( '見学会の日程', 'moegi-recruit' ),
	);
}

/**
 * 採用窓口の情報を取得する。
 *
 * @return array<string, string> 項目と値。
 */
function moegi_get_contact() {
	$saved = get_option( 'moegi_contact', array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), moegi_contact_defaults() );
}

/**
 * 電話番号の tel: の URL を返す。
 *
 * @return string URL。
 */
function moegi_tel_url() {
	return 'tel:' . preg_replace( '/[^0-9+]/', '', moegi_get_contact()['tel'] );
}
