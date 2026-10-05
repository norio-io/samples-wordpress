<?php
/**
 * 求人の構造化データ（schema.org の JobPosting）
 *
 * 募集中・急募の求人の詳細に、JSON-LD を出力する。募集停止・掲載期限切れの求人には出力しない。
 * 値はすべて入力欄から生成し、画面に表示する内容（求人名・仕事内容・掲載日・掲載期限・雇用形態・勤務施設の所在地・
 * 給与の範囲と単位・法人名）と一致させる。
 *
 * @package moegi-recruit
 */

/**
 * 求人の JobPosting を組み立てる。
 *
 * @param int $post_id 求人の投稿 ID。
 * @return array|null JobPosting。募集中でない求人では null。
 */
function moegi_job_posting( $post_id ) {
	$j = moegi_get_job( $post_id );
	if ( ! $j['open'] ) {
		return null;
	}

	$data = array(
		'@context'           => 'https://schema.org/',
		'@type'              => 'JobPosting',
		'title'              => get_the_title( $post_id ),
		// 仕事内容（本文）を、画面と同じ HTML で渡す。
		'description'        => trim( wp_kses_post( apply_filters( 'the_content', get_post_field( 'post_content', $post_id ) ) ) ),
		'datePosted'         => get_the_date( 'Y-m-d', $post_id ),
		'identifier'         => array(
			'@type' => 'PropertyValue',
			'name'  => get_bloginfo( 'name' ),
			'value' => 'job-' . $post_id,
		),
		'hiringOrganization' => array(
			'@type'  => 'Organization',
			'name'   => get_bloginfo( 'name' ),
			'sameAs' => home_url( '/' ),
		),
		'directApply'        => true,
	);

	if ( $j['deadline'] ) {
		// 掲載期限の当日の終わりまでを有効とする。
		$data['validThrough'] = ( new DateTimeImmutable( $j['deadline'] . ' 23:59:59', wp_timezone() ) )->format( 'c' );
	}

	$types = array();
	foreach ( $j['employments'] as $term ) {
		$types = array_merge( $types, moegi_employment_schema_types()[ $term->slug ] ?? array( 'OTHER' ) );
	}
	if ( $types ) {
		$data['employmentType'] = array_values( array_unique( $types ) );
	}

	if ( $j['facility'] ) {
		$f                   = moegi_get_facility( $j['facility'] );
		$data['jobLocation'] = array(
			'@type'   => 'Place',
			'name'    => get_the_title( $j['facility'] ),
			'address' => array_filter(
				array(
					'@type'           => 'PostalAddress',
					'postalCode'      => $f['postal_code'],
					'addressRegion'   => $f['region'],
					'addressLocality' => $f['locality'],
					'streetAddress'   => $f['street'],
					'addressCountry'  => 'JP',
				)
			),
		);
	}

	if ( $j['wage_min'] || $j['wage_max'] ) {
		$value = array(
			'@type'    => 'QuantitativeValue',
			'unitText' => 'hourly' === $j['wage_unit'] ? 'HOUR' : 'MONTH',
		);
		// 範囲の扱いは、画面の給与の表示と揃える。
		if ( $j['wage_max'] > $j['wage_min'] && $j['wage_min'] > 0 ) {
			$value['minValue'] = $j['wage_min'];
			$value['maxValue'] = $j['wage_max'];
		} elseif ( $j['wage_max'] > 0 && 0 === $j['wage_min'] ) {
			$value['maxValue'] = $j['wage_max'];
		} else {
			$value['value'] = $j['wage_min'];
		}
		$data['baseSalary'] = array(
			'@type'    => 'MonetaryAmount',
			'currency' => 'JPY',
			'value'    => $value,
		);
	}

	return $data;
}

/**
 * 求人の詳細に JobPosting の JSON-LD を出力する。
 */
function moegi_print_job_posting() {
	if ( ! is_singular( 'job' ) ) {
		return;
	}
	$data = moegi_job_posting( get_queried_object_id() );
	if ( ! $data ) {
		return;
	}
	wp_print_inline_script_tag(
		wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ),
		array( 'type' => 'application/ld+json' )
	);
}
add_action( 'wp_head', 'moegi_print_job_posting' );
