<?php
/**
 * 物件データの定義と表示書式
 *
 * 選択肢（設備・賃料上限・駅徒歩・並べ替え）と、賃料・面積・築年数などの表示書式を1か所にまとめる。
 * 公開側・管理画面・エディターのいずれもここを参照する。
 *
 * @package hinata-realty
 */

/**
 * 設備の選択肢。キーを投稿メタ equipment の値として保存する。
 *
 * @return array<string, string> キーと表示名。
 */
function hinata_equipment_options() {
	return array(
		'autolock'      => __( 'オートロック', 'hinata-realty' ),
		'delivery_box'  => __( '宅配ボックス', 'hinata-realty' ),
		'separate_bath' => __( 'バス・トイレ別', 'hinata-realty' ),
		'washstand'     => __( '独立洗面台', 'hinata-realty' ),
		'laundry'       => __( '室内洗濯機置場', 'hinata-realty' ),
		'reheat'        => __( '追い焚き', 'hinata-realty' ),
		'aircon'        => __( 'エアコン', 'hinata-realty' ),
		'flooring'      => __( 'フローリング', 'hinata-realty' ),
		'internet'      => __( 'インターネット無料', 'hinata-realty' ),
		'pets'          => __( 'ペット相談', 'hinata-realty' ),
		'bicycle'       => __( '駐輪場', 'hinata-realty' ),
		'parking'       => __( '駐車場', 'hinata-realty' ),
	);
}

/**
 * 掲載状態の選択肢。
 *
 * @return array<string, string> キーと表示名。
 */
function hinata_status_options() {
	return array(
		'available'  => __( '募集中', 'hinata-realty' ),
		'contracted' => __( '成約済み', 'hinata-realty' ),
	);
}

/**
 * 賃料上限の選択肢（円）。
 *
 * @return int[] 賃料上限。
 */
function hinata_rent_max_options() {
	return array( 50000, 60000, 70000, 80000, 100000, 120000, 150000 );
}

/**
 * 駅徒歩の上限の選択肢（分）。
 *
 * @return int[] 徒歩分数の上限。
 */
function hinata_walk_max_options() {
	return array( 5, 10, 15, 20 );
}

/**
 * 並べ替えの選択肢。
 *
 * @return array<string, string> キーと表示名。先頭を既定とする。
 */
function hinata_sort_options() {
	return array(
		'new'  => __( '新着順', 'hinata-realty' ),
		'rent' => __( '賃料の安い順', 'hinata-realty' ),
		'area' => __( '広い順', 'hinata-realty' ),
	);
}

/**
 * 新着とみなす日数。
 */
const HINATA_NEW_DAYS = 14;

/**
 * 金額を「万円」で表す。例: 78000 → 7.8万円、100000 → 10万円。
 *
 * @param int $yen 金額（円）。
 * @return string 表示用の文字列。
 */
function hinata_format_man_yen( $yen ) {
	$man = round( (int) $yen / 10000, 2 );
	$str = rtrim( rtrim( number_format( $man, 2, '.', '' ), '0' ), '.' );
	return $str . '万円';
}

/**
 * 金額を桁区切りの「円」で表す。0 は「なし」とする。
 *
 * @param int $yen 金額（円）。
 * @return string 表示用の文字列。
 */
function hinata_format_yen( $yen ) {
	$yen = (int) $yen;
	return 0 === $yen ? __( 'なし', 'hinata-realty' ) : number_format( $yen ) . '円';
}

/**
 * 敷金・礼金を「か月」で表す。0 は「なし」とする。
 *
 * @param float $months 月数。
 * @return string 表示用の文字列。
 */
function hinata_format_months( $months ) {
	$months = (float) $months;
	if ( 0.0 === $months ) {
		return __( 'なし', 'hinata-realty' );
	}
	return rtrim( rtrim( number_format( $months, 1, '.', '' ), '0' ), '.' ) . 'か月';
}

/**
 * 専有面積を「㎡」で表す。小数第1位まで。
 *
 * @param float $sqm 面積（㎡）。
 * @return string 表示用の文字列。
 */
function hinata_format_sqm( $sqm ) {
	return number_format( (float) $sqm, 1 ) . '㎡';
}

/**
 * 築年月から築年数を求める。新築（1年未満）は 0 を返す。
 *
 * @param string $built 築年月（YYYY-MM）。
 * @return int|null 築年数。書式が不正な場合は null。
 */
function hinata_building_age( $built ) {
	if ( ! preg_match( '/^(\d{4})-(\d{2})$/', (string) $built, $m ) ) {
		return null;
	}
	$now    = current_datetime();
	$months = ( (int) $now->format( 'Y' ) - (int) $m[1] ) * 12 + ( (int) $now->format( 'n' ) - (int) $m[2] );
	return max( 0, intdiv( $months, 12 ) );
}

/**
 * 築年数を表す。例: 築8年、新築。
 *
 * @param string $built 築年月（YYYY-MM）。
 * @return string 表示用の文字列。書式が不正な場合は空文字列。
 */
function hinata_format_age( $built ) {
	$age = hinata_building_age( $built );
	if ( null === $age ) {
		return '';
	}
	return 0 === $age ? __( '新築', 'hinata-realty' ) : sprintf( '築%d年', $age );
}

/**
 * 築年月を「2016年3月」の形で表す。
 *
 * @param string $built 築年月（YYYY-MM）。
 * @return string 表示用の文字列。
 */
function hinata_format_built( $built ) {
	if ( ! preg_match( '/^(\d{4})-(\d{2})$/', (string) $built, $m ) ) {
		return '';
	}
	return sprintf( '%d年%d月', (int) $m[1], (int) $m[2] );
}

/**
 * 物件の入力欄をまとめて取得する。
 *
 * @param int $post_id 物件の投稿 ID。
 * @return array 入力欄の値。
 */
function hinata_get_property( $post_id ) {
	$meta = static function ( $key ) use ( $post_id ) {
		return get_post_meta( $post_id, $key, true );
	};
	$area = get_the_terms( $post_id, 'area' );
	$lay  = get_the_terms( $post_id, 'layout' );
	return array(
		'rent'       => (int) $meta( 'rent' ),
		'fee'        => (int) $meta( 'fee' ),
		'deposit'    => (float) $meta( 'deposit' ),
		'key_money'  => (float) $meta( 'key_money' ),
		'sqm'        => (float) $meta( 'sqm' ),
		'floor'      => (int) $meta( 'floor' ),
		'floors'     => (int) $meta( 'floors' ),
		'built'      => (string) $meta( 'built' ),
		'station'    => (string) $meta( 'station' ),
		'walk'       => (int) $meta( 'walk' ),
		'address'    => (string) $meta( 'address' ),
		'equipment'  => array_values( array_filter( (array) get_post_meta( $post_id, 'equipment', false ) ) ), // 設備は値ごとに1行で保存しているため、すべての行を取得する。
		'status'     => (string) $meta( 'status' ) ? (string) $meta( 'status' ) : 'available',
		'area_term'  => is_array( $area ) && $area ? $area[0] : null,
		'layout'     => is_array( $lay ) && $lay ? $lay[0]->name : '',
		'is_new'     => ( time() - (int) get_post_time( 'U', true, $post_id ) ) < HINATA_NEW_DAYS * DAY_IN_SECONDS,
		'contracted' => 'contracted' === $meta( 'status' ),
	);
}
