<?php
/**
 * デモデータの初期設定
 *
 * blueprint の runPHP から、WXR の取り込み後に1回だけ実行する。
 *
 * - 求人の掲載日と掲載期限を「実行した日から数えた日数」に置き換える。いつ開いても、掲載期限を過ぎた求人が
 *   1件だけあり、それ以外の求人は期限内となるようにするため
 * - 採用窓口の情報を保存する。見学会の日程は、実行した日より後の日付とする
 *
 * 架空の題材であり、法人名・施設名・求人・職員名・所在地・電話番号はすべてサンプル用に作成したものです。
 *
 * @package moegi-recruit
 */

$moegi_now = current_datetime()->setTime( 10, 0 );

$moegi_jobs = get_posts(
	array(
		'post_type'      => 'job',
		'post_status'    => 'any',
		'posts_per_page' => -1,
	)
);

foreach ( $moegi_jobs as $moegi_job ) {
	$moegi_days     = (int) get_post_meta( $moegi_job->ID, '_demo_days_ago', true );
	$moegi_deadline = (int) get_post_meta( $moegi_job->ID, '_demo_deadline_days', true );
	$moegi_posted   = $moegi_now->modify( sprintf( '-%d days', $moegi_days ) );

	wp_update_post(
		array(
			'ID'            => $moegi_job->ID,
			'post_date'     => $moegi_posted->format( 'Y-m-d H:i:s' ),
			'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $moegi_posted->getTimestamp() ),
		)
	);
	update_post_meta( $moegi_job->ID, 'deadline', $moegi_now->modify( sprintf( '%+d days', $moegi_deadline ) )->format( 'Y-m-d' ) );

	delete_post_meta( $moegi_job->ID, '_demo_days_ago' );
	delete_post_meta( $moegi_job->ID, '_demo_deadline_days' );
}

/**
 * 基準日より後で、指定の曜日にあたる最初の日を返す。
 *
 * @param DateTimeImmutable $from    基準日。
 * @param int               $after   基準日から何日後以降とするか。
 * @param int               $weekday 曜日（0: 日曜 〜 6: 土曜）。
 * @return DateTimeImmutable 日付。
 */
function moegi_demo_next_weekday( $from, $after, $weekday ) {
	$day = $from->modify( sprintf( '+%d days', $after ) );
	while ( (int) $day->format( 'w' ) !== $weekday ) {
		$day = $day->modify( '+1 day' );
	}
	return $day;
}

$moegi_week  = array( '日', '月', '火', '水', '木', '金', '土' );
$moegi_tours = array(
	array( moegi_demo_next_weekday( $moegi_now, 7, 6 ), '10:00', '特別養護老人ホーム もえぎの里' ),
	array( moegi_demo_next_weekday( $moegi_now, 14, 3 ), '14:00', 'デイサービスセンター もえぎ' ),
	array( moegi_demo_next_weekday( $moegi_now, 21, 6 ), '10:00', 'グループホーム もえぎの家' ),
);
$moegi_lines = array();
foreach ( $moegi_tours as $moegi_tour ) {
	list( $moegi_day, $moegi_time, $moegi_place ) = $moegi_tour;
	$moegi_lines[]                                = sprintf( '%s（%s）%s– %s', $moegi_day->format( 'n月j日' ), $moegi_week[ (int) $moegi_day->format( 'w' ) ], $moegi_time, $moegi_place );
}

update_option(
	'moegi_contact',
	array_merge(
		moegi_contact_defaults(),
		array( 'tour' => implode( "\n", $moegi_lines ) )
	)
);
