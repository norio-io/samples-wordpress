<?php
/**
 * 動的ブロック
 *
 * 求人・施設・職員の声の値の組み合わせで表示が決まる部分（求人カード、絞り込み、件数と並べ替え、主要項目、
 * 給与と手当の内訳、応募の導線など）をサーバー側で描画するブロックとして登録する。エディターでは
 * assets/js/editor.js が同じブロックを登録し、サーバー側の描画結果を表示する。
 *
 * @package moegi-recruit
 */

/**
 * テーマのブロックの定義。
 *
 * @return array<string, array> ブロック名と登録の引数。
 */
function moegi_block_definitions() {
	$level   = array(
		'level' => array(
			'type'    => 'integer',
			'default' => 3,
		),
	);
	$context = array( 'postId' );
	return array(
		'moegi/job-card'        => array(
			'title'           => __( '求人カード', 'moegi-recruit' ),
			'attributes'      => $level,
			'uses_context'    => $context,
			'render_callback' => 'moegi_render_job_card',
		),
		'moegi/facility-card'   => array(
			'title'           => __( '施設カード', 'moegi-recruit' ),
			'attributes'      => $level,
			'uses_context'    => $context,
			'render_callback' => 'moegi_render_facility_card',
		),
		'moegi/voice-card'      => array(
			'title'           => __( '職員の声カード', 'moegi-recruit' ),
			'attributes'      => $level,
			'uses_context'    => $context,
			'render_callback' => 'moegi_render_voice_card',
		),
		'moegi/job-filter'      => array(
			'title'           => __( '求人の絞り込み', 'moegi-recruit' ),
			'render_callback' => 'moegi_render_job_filter',
		),
		'moegi/result-bar'      => array(
			'title'           => __( '件数・条件・並べ替え', 'moegi-recruit' ),
			'render_callback' => 'moegi_render_result_bar',
		),
		'moegi/job-head'        => array(
			'title'           => __( '求人の募集状況と札', 'moegi-recruit' ),
			'uses_context'    => $context,
			'render_callback' => 'moegi_render_job_head',
		),
		'moegi/job-wage'        => array(
			'title'           => __( '求人の給与', 'moegi-recruit' ),
			'uses_context'    => $context,
			'render_callback' => 'moegi_render_job_wage',
		),
		'moegi/job-summary'     => array(
			'title'           => __( '求人の主要項目', 'moegi-recruit' ),
			'uses_context'    => $context,
			'render_callback' => 'moegi_render_job_summary',
		),
		'moegi/job-pay'         => array(
			'title'           => __( '給与と手当の内訳', 'moegi-recruit' ),
			'uses_context'    => $context,
			'render_callback' => 'moegi_render_job_pay',
		),
		'moegi/job-schedule'    => array(
			'title'           => __( '勤務時間と休日', 'moegi-recruit' ),
			'uses_context'    => $context,
			'render_callback' => 'moegi_render_job_schedule',
		),
		'moegi/job-requirement' => array(
			'title'           => __( '応募の条件', 'moegi-recruit' ),
			'uses_context'    => $context,
			'render_callback' => 'moegi_render_job_requirement',
		),
		'moegi/job-facility'    => array(
			'title'           => __( '勤務施設', 'moegi-recruit' ),
			'uses_context'    => $context,
			'render_callback' => 'moegi_render_job_facility',
		),
		'moegi/job-cta'         => array(
			'title'           => __( '応募の導線', 'moegi-recruit' ),
			'attributes'      => array(
				'variant' => array(
					'type'    => 'string',
					'default' => 'side',
				),
			),
			'uses_context'    => $context,
			'render_callback' => 'moegi_render_job_cta',
		),
		'moegi/facility-art'    => array(
			'title'           => __( '施設のイラスト', 'moegi-recruit' ),
			'uses_context'    => $context,
			'render_callback' => 'moegi_render_facility_art',
		),
		'moegi/facility-spec'   => array(
			'title'           => __( '施設の概要', 'moegi-recruit' ),
			'uses_context'    => $context,
			'render_callback' => 'moegi_render_facility_spec',
		),
		'moegi/portrait'        => array(
			'title'           => __( '職員のイラスト', 'moegi-recruit' ),
			'uses_context'    => $context,
			'render_callback' => 'moegi_render_portrait',
		),
		'moegi/voice-profile'   => array(
			'title'           => __( '職員のプロフィール', 'moegi-recruit' ),
			'uses_context'    => $context,
			'render_callback' => 'moegi_render_voice_profile',
		),
		'moegi/voice-day'       => array(
			'title'           => __( '1日の流れ', 'moegi-recruit' ),
			'uses_context'    => $context,
			'render_callback' => 'moegi_render_voice_day',
		),
		'moegi/type-entry'      => array(
			'title'           => __( '職種から探す', 'moegi-recruit' ),
			'render_callback' => 'moegi_render_type_entry',
		),
		'moegi/contact-info'    => array(
			'title'           => __( '採用窓口', 'moegi-recruit' ),
			'render_callback' => 'moegi_render_contact_info',
		),
		'moegi/tour'            => array(
			'title'           => __( '見学会の日程', 'moegi-recruit' ),
			'render_callback' => 'moegi_render_tour',
		),
		'moegi/entry-form'      => array(
			'title'           => __( '応募フォーム（表示のみ）', 'moegi-recruit' ),
			'render_callback' => 'moegi_render_entry_form',
		),
	);
}

/**
 * ブロックを登録する。
 */
function moegi_register_blocks() {
	foreach ( moegi_block_definitions() as $name => $args ) {
		register_block_type(
			$name,
			array_merge(
				array(
					'api_version' => 3,
					'category'    => 'theme',
					'supports'    => array( 'html' => false ),
				),
				$args
			)
		);
	}
}
add_action( 'init', 'moegi_register_blocks' );

/**
 * 応募フォームで、対象の求人を受け取るクエリ変数を登録する。
 *
 * job は求人の投稿タイプのクエリ変数と重なるため、job_id とする。
 *
 * @param string[] $vars 公開クエリ変数。
 * @return string[] 追加後の公開クエリ変数。
 */
function moegi_entry_query_vars( $vars ) {
	$vars[] = 'job_id';
	return $vars;
}
add_filter( 'query_vars', 'moegi_entry_query_vars' );

// ------------------------------------------------------------ 共通

/**
 * テーマ同梱の SVG アイコンを返す。装飾として扱い、支援技術からは隠す。
 *
 * @param string $name アイコン名（assets/icons/<name>.svg）。
 * @return string SVG。
 */
function moegi_icon( $name ) {
	return moegi_svg( 'icons/' . sanitize_file_name( $name ), 'class="icon" aria-hidden="true" focusable="false"' );
}

/**
 * テーマ同梱の SVG を読み、ルート要素に属性を加えて返す。
 *
 * @param string $path       assets/ からのパス（拡張子を除く）。
 * @param string $attributes ルート要素に加える属性。
 * @return string SVG。ファイルがない場合は空文字列。
 */
function moegi_svg( $path, $attributes ) {
	$file = get_theme_file_path( 'assets/' . $path . '.svg' );
	if ( ! is_readable( $file ) ) {
		return '';
	}
	// テーマに同梱したファイルのみを読むため、リモートの取得に用いる関数は不要。
	$svg = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$svg = preg_replace( '/<!--.*?-->\s*/s', '', trim( $svg ) );
	return preg_replace( '/^<svg /', '<svg ' . $attributes . ' ', $svg );
}

/**
 * 描画中の投稿の ID を返す。
 *
 * @param WP_Block|null $block     ブロック。
 * @param string        $post_type 投稿タイプ。
 * @return int 投稿 ID。投稿タイプが異なる場合は 0。
 */
function moegi_current_id( $block, $post_type ) {
	$post_id = (int) ( $block->context['postId'] ?? get_the_ID() );
	return $post_id && get_post_type( $post_id ) === $post_type ? $post_id : 0;
}

/**
 * エディターで対象の投稿がない場合の表示。
 *
 * @param string $label ブロックの名前。
 * @return string HTML。
 */
function moegi_block_placeholder( $label ) {
	return sprintf( '<div %s><p>%s</p></div>', get_block_wrapper_attributes( array( 'class' => 'moegi-placeholder' ) ), esc_html( $label ) );
}

/**
 * 見出しの階層を 2〜4 に収める。
 *
 * @param array $attributes 属性。
 * @return int 見出しの階層。
 */
function moegi_heading_level( $attributes ) {
	return min( 4, max( 2, (int) ( $attributes['level'] ?? 3 ) ) );
}

/**
 * 2列の定義リスト。値が空の行は出力しない。
 *
 * @param array<string, string> $rows    見出しと値。
 * @param string                $wrapper ルート要素の属性。
 * @return string HTML。
 */
function moegi_definition_list( $rows, $wrapper ) {
	$html = '';
	foreach ( $rows as $label => $value ) {
		if ( '' === (string) $value ) {
			continue;
		}
		$html .= sprintf( '<div><dt>%1$s</dt><dd>%2$s</dd></div>', esc_html( $label ), esc_html( $value ) );
	}
	return sprintf( '<dl %1$s>%2$s</dl>', $wrapper, $html );
}

/**
 * 選択肢の option 要素を返す。
 *
 * @param array  $options  値と表示名。
 * @param string $selected 選択中の値。
 * @return string HTML。
 */
function moegi_options_html( $options, $selected ) {
	$html = '';
	foreach ( $options as $value => $label ) {
		$html .= sprintf( '<option value="%s"%s>%s</option>', esc_attr( $value ), selected( (string) $value, (string) $selected, false ), esc_html( $label ) );
	}
	return $html;
}

/**
 * 分類の語を「指定なし」付きの選択肢として返す。
 *
 * @param string $taxonomy 分類。
 * @return array<string, string> スラッグと名前。
 */
function moegi_term_options( $taxonomy ) {
	$list = array( '' => __( '指定なし', 'moegi-recruit' ) );
	foreach ( get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'orderby'    => 'term_id',
		)
	) as $term ) {
		$list[ $term->slug ] = $term->name;
	}
	return $list;
}

/**
 * 公開中の施設を「指定なし」付きの選択肢として返す。
 *
 * @return array<string, string> スラッグと施設名。
 */
function moegi_facility_options() {
	$list = array( '' => __( '指定なし', 'moegi-recruit' ) );
	foreach ( get_posts(
		array(
			'post_type'      => 'facility',
			'posts_per_page' => -1,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
		)
	) as $post ) {
		$list[ $post->post_name ] = get_the_title( $post );
	}
	return $list;
}

// ------------------------------------------------------------ 札

/**
 * 募集状況の札。募集中は札を出さず、急募・募集停止・期限切れの場合に出す。
 *
 * @param array $j 求人の値（moegi_get_job() の戻り値）。
 * @return string HTML。
 */
function moegi_state_badge( $j ) {
	if ( 'open' === $j['state'] ) {
		return '';
	}
	// 期限切れは公開側では募集停止と同じ表示とする。
	$label = 'urgent' === $j['state'] ? $j['state_label'] : __( '募集停止', 'moegi-recruit' );
	return sprintf( '<span class="state-badge is-%1$s">%2$s</span>', esc_attr( 'urgent' === $j['state'] ? 'urgent' : 'closed' ), esc_html( $label ) );
}

/**
 * 職種と雇用形態の札。
 *
 * @param array $j 求人の値。
 * @return string HTML。
 */
function moegi_job_tags( $j ) {
	$html = '';
	foreach ( $j['job_types'] as $term ) {
		$html .= sprintf( '<span class="tag is-type">%s</span>', esc_html( $term->name ) );
	}
	foreach ( $j['employments'] as $term ) {
		$html .= sprintf( '<span class="tag is-employment">%s</span>', esc_html( $term->name ) );
	}
	return $html;
}

/**
 * 給与の表示（単位・金額・円）。金額を最も大きく示すため、要素を分けて出力する。
 *
 * @param array $j 求人の値。
 * @return string HTML。
 */
function moegi_wage_html( $j ) {
	return sprintf(
		'<p class="wage"><span class="wage__unit">%1$s</span><span class="wage__num num">%2$s</span><span class="wage__yen">%3$s</span></p>',
		esc_html( $j['wage_unit_label'] ),
		esc_html( moegi_wage_range( $j['wage_min'], $j['wage_max'] ) ),
		esc_html__( '円', 'moegi-recruit' )
	);
}

// ------------------------------------------------------------ カード

/**
 * 求人カード。
 *
 * 上段に職種と雇用形態の札、求人名、勤務施設を置く。中段に給与を最も大きく示す。下段に夜勤の有無、年間休日、
 * 未経験の応募の可否を置き、急募の札を右上に付ける。求人名のリンクを CSS でカード全体へ広げる。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function moegi_render_job_card( $attributes, $content, $block ) {
	$post_id = moegi_current_id( $block, 'job' );
	if ( ! $post_id ) {
		return moegi_block_placeholder( __( '求人カード', 'moegi-recruit' ) );
	}
	$j     = moegi_get_job( $post_id );
	$level = moegi_heading_level( $attributes );
	$meta  = array(
		array( 'night', __( '夜勤', 'moegi-recruit' ), moegi_format_night( $j['night_shifts'] ), $j['night_shifts'] > 0 ? '' : ' is-none' ),
		// カードは幅が狭いため、日数を定めない場合は短い表記とする。
		array( 'holiday', __( '年間休日', 'moegi-recruit' ), $j['holidays'] > 0 ? moegi_format_holidays( $j['holidays'] ) : __( 'シフト制', 'moegi-recruit' ), '' ),
		array( 'beginner', __( '未経験', 'moegi-recruit' ), $j['inexperienced'] ? __( '応募可', 'moegi-recruit' ) : __( '経験者', 'moegi-recruit' ), $j['inexperienced'] ? ' is-yes' : ' is-none' ),
	);
	$items = '';
	foreach ( $meta as $item ) {
		list( $icon, $label, $value, $class ) = $item;
		$items                               .= sprintf( '<div class="job-card__fact%1$s">%2$s<dt>%3$s</dt><dd>%4$s</dd></div>', esc_attr( $class ), moegi_icon( $icon ), esc_html( $label ), esc_html( $value ) );
	}

	return sprintf(
		'<article %1$s>%2$s<div class="job-card__head"><p class="job-card__tags">%3$s</p><h%4$d class="job-card__title"><a href="%5$s">%6$s</a></h%4$d><p class="job-card__place">%7$s%8$s</p></div>'
		. '<div class="job-card__wage">%9$s</div><dl class="job-card__facts">%10$s</dl></article>',
		get_block_wrapper_attributes( array( 'class' => 'job-card is-' . $j['state'] ) ),
		moegi_state_badge( $j ),
		moegi_job_tags( $j ),
		$level,
		esc_url( get_permalink( $post_id ) ),
		esc_html( get_the_title( $post_id ) ),
		moegi_icon( 'facility' ),
		esc_html( $j['facility'] ? get_the_title( $j['facility'] ) : __( '勤務施設は面接時にご相談', 'moegi-recruit' ) ),
		moegi_wage_html( $j ),
		$items
	);
}

/**
 * 施設のイラストの HTML。
 *
 * @param int $post_id 施設の投稿 ID。
 * @return string HTML。
 */
function moegi_facility_art_html( $post_id ) {
	$key = moegi_get_facility( $post_id )['art'];
	/* translators: %s: 施設名 */
	$label = sprintf( __( '%s の外観のイラスト', 'moegi-recruit' ), get_the_title( $post_id ) );
	$svg   = $key ? moegi_svg( 'facilities/' . sanitize_file_name( $key ), sprintf( 'role="img" aria-label="%s"', esc_attr( $label ) ) ) : '';
	return $svg ? $svg : moegi_svg( 'facilities/none', 'aria-hidden="true" focusable="false"' );
}

/**
 * 施設カード。イラスト、施設種別、施設名、定員と交通、募集中の求人数を置く。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function moegi_render_facility_card( $attributes, $content, $block ) {
	$post_id = moegi_current_id( $block, 'facility' );
	if ( ! $post_id ) {
		return moegi_block_placeholder( __( '施設カード', 'moegi-recruit' ) );
	}
	$f     = moegi_get_facility( $post_id );
	$level = moegi_heading_level( $attributes );
	$count = moegi_count_open_jobs( array( 'facility' => $post_id ) );
	return sprintf(
		'<article %1$s><div class="facility-card__art">%2$s</div><div class="facility-card__body"><p class="facility-card__kind">%3$s</p><h%4$d class="facility-card__name"><a href="%5$s">%6$s</a></h%4$d>'
		. '<dl class="facility-card__facts"><div>%7$s<dt>%8$s</dt><dd class="num">%9$s</dd></div><div>%10$s<dt>%11$s</dt><dd>%12$s</dd></div></dl><p class="facility-card__jobs"><span class="num">%13$d</span>%14$s</p></div></article>',
		get_block_wrapper_attributes( array( 'class' => 'facility-card' ) ),
		moegi_facility_art_html( $post_id ),
		esc_html( $f['kind'] ),
		$level,
		esc_url( get_permalink( $post_id ) ),
		esc_html( get_the_title( $post_id ) ),
		moegi_icon( 'people' ),
		esc_html__( '定員', 'moegi-recruit' ),
		/* translators: %d: 定員 */
		esc_html( sprintf( __( '%d名', 'moegi-recruit' ), $f['capacity'] ) ),
		moegi_icon( 'access' ),
		esc_html__( '交通', 'moegi-recruit' ),
		esc_html( $f['access'] ),
		$count,
		esc_html__( '件の求人を募集中', 'moegi-recruit' )
	);
}

/**
 * 職員のイラストの HTML。
 *
 * @param int $post_id 職員の声の投稿 ID。
 * @return string HTML。
 */
function moegi_portrait_html( $post_id ) {
	$key = moegi_get_voice( $post_id )['portrait'];
	/* translators: %s: 職員名 */
	$label = sprintf( __( '%s のイラスト', 'moegi-recruit' ), get_the_title( $post_id ) );
	$svg   = $key ? moegi_svg( 'portraits/' . sanitize_file_name( $key ), sprintf( 'class="portrait__svg" role="img" aria-label="%s"', esc_attr( $label ) ) ) : '';
	return $svg ? $svg : moegi_svg( 'portraits/none', 'class="portrait__svg" aria-hidden="true" focusable="false"' );
}

/**
 * 職員の声カード。イラスト、ひとこと（抜粋）、名前、職種・勤務施設・入職年を置く。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function moegi_render_voice_card( $attributes, $content, $block ) {
	$post_id = moegi_current_id( $block, 'voice' );
	if ( ! $post_id ) {
		return moegi_block_placeholder( __( '職員の声カード', 'moegi-recruit' ) );
	}
	return moegi_voice_card_html( $post_id, moegi_heading_level( $attributes ), get_block_wrapper_attributes( array( 'class' => 'voice-card' ) ) );
}

/**
 * 職員の声カードの HTML。
 *
 * @param int    $post_id 職員の声の投稿 ID。
 * @param int    $level   見出しの階層。
 * @param string $wrapper ルート要素の属性。
 * @return string HTML。
 */
function moegi_voice_card_html( $post_id, $level, $wrapper = 'class="voice-card"' ) {
	return sprintf(
		'<article %1$s><div class="portrait">%2$s</div><div class="voice-card__body"><p class="voice-card__quote">%3$s</p><h%4$d class="voice-card__name"><a href="%5$s">%6$s</a></h%4$d><p class="voice-card__meta">%7$s</p></div></article>',
		$wrapper,
		moegi_portrait_html( $post_id ),
		esc_html( get_the_excerpt( $post_id ) ),
		$level,
		esc_url( get_permalink( $post_id ) ),
		esc_html( get_the_title( $post_id ) ),
		esc_html( moegi_voice_meta_text( $post_id ) )
	);
}

/**
 * 職員の声の「職種 ／ 勤務施設 ／ 入職年」。
 *
 * @param int $post_id 職員の声の投稿 ID。
 * @return string 表示用の文字列。
 */
function moegi_voice_meta_text( $post_id ) {
	$v     = moegi_get_voice( $post_id );
	$parts = array_map(
		static function ( $term ) {
			return $term->name;
		},
		$v['job_types']
	);
	if ( $v['facility'] ) {
		$parts[] = get_the_title( $v['facility'] );
	}
	if ( $v['joined'] ) {
		/* translators: %d: 入職年 */
		$parts[] = sprintf( __( '%d年入職', 'moegi-recruit' ), $v['joined'] );
	}
	return implode( ' ／ ', $parts );
}

// ------------------------------------------------------------ 求人一覧

/**
 * 選択肢のラジオボタンの組。
 *
 * @param string $name     クエリ変数の名前。
 * @param string $legend   組の見出し。
 * @param array  $options  値と表示名。値が空文字列の選択肢を「指定なし」とする。
 * @param string $selected 選択中の値。
 * @param string $uid      id の接頭辞。
 * @return string HTML。
 */
function moegi_radio_group( $name, $legend, $options, $selected, $uid ) {
	$html = '';
	foreach ( $options as $value => $label ) {
		$id    = $uid . '-' . $name . '-' . ( '' === (string) $value ? 'all' : $value );
		$html .= sprintf(
			'<label class="choice" for="%1$s"><input type="radio" id="%1$s" name="%2$s" value="%3$s"%4$s><span>%5$s</span></label>',
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( $value ),
			checked( (string) $value, (string) $selected, false ),
			esc_html( $label )
		);
	}
	return sprintf( '<fieldset class="filter__group"><legend>%1$s</legend><div class="filter__choices">%2$s</div></fieldset>', esc_html( $legend ), $html );
}

/**
 * 求人の絞り込み（求人一覧の左段）。職種・雇用形態・勤務施設・未経験の応募の可否・夜勤の有無を選ぶ。
 *
 * 職種別の一覧では職種が URL で決まるため、職種の選択肢を出さず、送信先を職種別の一覧とする。
 * 幅の狭い画面では assets/js/front.js が折りたたむ。スクリプトが動かない場合は開いたまま表示する。
 *
 * @return string HTML。
 */
function moegi_render_job_filter() {
	$filters = moegi_get_filters();
	$uid     = wp_unique_id( 'moegi-filter-' );
	$term    = get_queried_object();
	$on_term = $term instanceof WP_Term && 'job_type' === $term->taxonomy;
	$none    = array( '' => __( '指定なし', 'moegi-recruit' ) );

	$fields = '';
	if ( ! $on_term ) {
		$fields .= moegi_radio_group( 'job_type', __( '職種', 'moegi-recruit' ), moegi_term_options( 'job_type' ), $filters['job_type'], $uid );
	}
	$fields .= moegi_radio_group( 'employment', __( '雇用形態', 'moegi-recruit' ), moegi_term_options( 'employment' ), $filters['employment'], $uid );
	$fields .= sprintf(
		'<div class="filter__group"><label class="filter__label" for="%1$s-place">%2$s</label><select id="%1$s-place" name="place">%3$s</select></div>',
		esc_attr( $uid ),
		esc_html__( '勤務施設', 'moegi-recruit' ),
		moegi_options_html( moegi_facility_options(), $filters['place'] )
	);
	$fields .= moegi_radio_group( 'night', __( '夜勤', 'moegi-recruit' ), $none + moegi_night_options(), $filters['night'], $uid );
	$fields .= sprintf(
		'<fieldset class="filter__group"><legend>%1$s</legend><label class="check" for="%2$s-inexperienced"><input type="checkbox" id="%2$s-inexperienced" name="inexperienced" value="1"%3$s><span>%4$s</span></label></fieldset>',
		esc_html__( '経験', 'moegi-recruit' ),
		esc_attr( $uid ),
		checked( $filters['inexperienced'], true, false ),
		esc_html__( '未経験から応募できる求人', 'moegi-recruit' )
	);
	if ( 'new' !== $filters['sort'] ) {
		$fields .= sprintf( '<input type="hidden" name="sort" value="%s">', esc_attr( $filters['sort'] ) );
	}

	$form = sprintf(
		'<form class="filter__form" action="%1$s" method="get" role="search" aria-label="%2$s">%3$s<button type="submit" class="filter__submit">%4$s</button></form>',
		esc_url( $on_term ? get_term_link( $term ) : get_post_type_archive_link( 'job' ) ),
		esc_attr__( '求人の絞り込み', 'moegi-recruit' ),
		$fields,
		esc_html__( 'この条件で探す', 'moegi-recruit' )
	);

	return sprintf(
		'<div %1$s><details class="filter__details" open><summary>%2$s</summary>%3$s</details></div>',
		get_block_wrapper_attributes(),
		esc_html__( '条件で絞り込む', 'moegi-recruit' ),
		$form
	);
}

/**
 * 件数・選択中の条件（解除できるチップ）・並べ替え。
 *
 * @return string HTML。
 */
function moegi_render_result_bar() {
	global $wp_query;
	$filters = moegi_get_filters();
	$term    = get_queried_object();
	$on_term = $term instanceof WP_Term && 'job_type' === $term->taxonomy;
	$base    = $on_term ? get_term_link( $term ) : get_post_type_archive_link( 'job' );
	if ( $on_term ) {
		$filters['job_type'] = '';
	}

	$url = static function ( $changes ) use ( $filters, $base ) {
		$next = array_merge( $filters, $changes );
		$args = array_filter(
			array(
				'job_type'      => $next['job_type'],
				'employment'    => $next['employment'],
				'place'         => $next['place'],
				'night'         => $next['night'],
				'inexperienced' => $next['inexperienced'] ? '1' : '',
				'sort'          => 'new' === $next['sort'] ? '' : $next['sort'],
			)
		);
		return esc_url( add_query_arg( $args, $base ) );
	};

	$chips = array();
	foreach ( array(
		'job_type'   => __( '職種', 'moegi-recruit' ),
		'employment' => __( '雇用形態', 'moegi-recruit' ),
	) as $taxonomy => $label ) {
		if ( $filters[ $taxonomy ] ) {
			$t       = get_term_by( 'slug', $filters[ $taxonomy ], $taxonomy );
			$chips[] = array( $label, $t ? $t->name : $filters[ $taxonomy ], array( $taxonomy => '' ) );
		}
	}
	if ( $filters['place'] ) {
		$chips[] = array( __( '勤務施設', 'moegi-recruit' ), get_the_title( moegi_facility_by_slug( $filters['place'] ) ), array( 'place' => '' ) );
	}
	if ( $filters['night'] ) {
		$chips[] = array( __( '夜勤', 'moegi-recruit' ), moegi_night_options()[ $filters['night'] ], array( 'night' => '' ) );
	}
	if ( $filters['inexperienced'] ) {
		$chips[] = array( __( '経験', 'moegi-recruit' ), __( '未経験可', 'moegi-recruit' ), array( 'inexperienced' => false ) );
	}

	$chips_html = '';
	if ( $chips ) {
		$items = '';
		foreach ( $chips as $chip ) {
			list( $label, $value, $changes ) = $chip;
			$items                          .= sprintf(
				'<li><a class="chip" href="%1$s" aria-label="%2$s"><span class="chip__label">%3$s</span>%4$s<span class="chip__x" aria-hidden="true">×</span></a></li>',
				$url( $changes ),
				/* translators: 1: 条件の種類 2: 条件の値 */
				esc_attr( sprintf( __( '条件を解除: %1$s %2$s', 'moegi-recruit' ), $label, $value ) ),
				esc_html( $label ),
				esc_html( $value )
			);
		}
		$items     .= sprintf(
			'<li><a class="chip-clear" href="%1$s">%2$s</a></li>',
			$url(
				array(
					'job_type'      => '',
					'employment'    => '',
					'place'         => '',
					'night'         => '',
					'inexperienced' => false,
				)
			),
			esc_html__( 'すべて解除', 'moegi-recruit' )
		);
		$chips_html = sprintf( '<ul class="result-bar__chips" aria-label="%1$s">%2$s</ul>', esc_attr__( '選択中の条件', 'moegi-recruit' ), $items );
	}

	$sort = '';
	foreach ( moegi_sort_options() as $key => $label ) {
		$sort .= sprintf(
			'<li><a href="%1$s"%2$s>%3$s</a></li>',
			$url( array( 'sort' => $key ) ),
			$key === $filters['sort'] ? ' aria-current="true"' : '',
			esc_html( $label )
		);
	}
	$note = 'wage' === $filters['sort'] ? sprintf( '<p class="result-bar__note">%s</p>', esc_html__( '月給の求人を先に、時給の求人を後に、それぞれ給与の下限が高い順に並べています。急募の求人は先頭に置いています。', 'moegi-recruit' ) ) : '';

	return sprintf(
		'<div %1$s><div class="result-bar__row"><p class="result-bar__count" role="status"><span class="num">%2$s</span>%3$s</p><nav class="result-bar__sort" aria-label="%4$s"><ul>%5$s</ul></nav></div>%6$s%7$s</div>',
		get_block_wrapper_attributes(),
		esc_html( number_format( (int) $wp_query->found_posts ) ),
		esc_html__( '件の求人', 'moegi-recruit' ),
		esc_attr__( '並べ替え', 'moegi-recruit' ),
		$sort,
		$note,
		$chips_html
	);
}

// ------------------------------------------------------------ 求人詳細

/**
 * 求人の詳細の冒頭に置く募集状況と札（職種・雇用形態）、勤務施設。
 * 募集停止・掲載期限切れの求人では、募集を停止している旨を加える。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function moegi_render_job_head( $attributes, $content, $block ) {
	$post_id = moegi_current_id( $block, 'job' );
	if ( ! $post_id ) {
		return moegi_block_placeholder( __( '求人の募集状況と札', 'moegi-recruit' ) );
	}
	$j      = moegi_get_job( $post_id );
	$notice = '';
	if ( ! $j['open'] ) {
		$notice = sprintf(
			'<p class="job-head__notice" role="status"><strong>%1$s</strong>%2$s</p>',
			esc_html__( '現在、この求人の募集を停止しています。', 'moegi-recruit' ),
			esc_html__( '同じ職種で募集中の求人をご覧ください。募集を再開した場合は、このページでお知らせします。', 'moegi-recruit' )
		);
	}
	$place = $j['facility'] ? sprintf( '<p class="job-head__place">%1$s<a href="%2$s">%3$s</a></p>', moegi_icon( 'facility' ), esc_url( get_permalink( $j['facility'] ) ), esc_html( get_the_title( $j['facility'] ) ) ) : '';
	return sprintf(
		'<div %1$s><p class="job-head__tags">%2$s%3$s</p>%4$s%5$s</div>',
		get_block_wrapper_attributes( array( 'class' => 'job-head' ) ),
		moegi_state_badge( $j ),
		moegi_job_tags( $j ),
		$place,
		$notice
	);
}

/**
 * 求人の給与（詳細の右段）。給与を最も大きく示し、賞与を併記する。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function moegi_render_job_wage( $attributes, $content, $block ) {
	$post_id = moegi_current_id( $block, 'job' );
	if ( ! $post_id ) {
		return moegi_block_placeholder( __( '求人の給与', 'moegi-recruit' ) );
	}
	$j = moegi_get_job( $post_id );
	/* translators: %s: 賞与 */
	$bonus = $j['bonus'] ? sprintf( '<p class="job-wage__bonus">%1$s</p>', esc_html( sprintf( __( '賞与 %s', 'moegi-recruit' ), $j['bonus'] ) ) ) : '';
	return sprintf( '<div %1$s>%2$s%3$s</div>', get_block_wrapper_attributes( array( 'class' => 'job-wage' ) ), moegi_wage_html( $j ), $bonus );
}

/**
 * 求人の主要項目（右段）。夜勤・年間休日・雇用形態・未経験の応募・必要な資格・掲載日と掲載期限を示す。
 *
 * 掲載日と掲載期限は、構造化データ（datePosted / validThrough）と一致させるため画面にも表示する。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function moegi_render_job_summary( $attributes, $content, $block ) {
	$post_id = moegi_current_id( $block, 'job' );
	if ( ! $post_id ) {
		return moegi_block_placeholder( __( '求人の主要項目', 'moegi-recruit' ) );
	}
	$j     = moegi_get_job( $post_id );
	$items = array(
		array( 'night', __( '夜勤', 'moegi-recruit' ), moegi_format_night( $j['night_shifts'] ) ),
		array( 'holiday', __( '年間休日', 'moegi-recruit' ), moegi_format_holidays( $j['holidays'] ) ),
		array( 'employment', __( '雇用形態', 'moegi-recruit' ), implode( '、', wp_list_pluck( $j['employments'], 'name' ) ) ),
		array( 'beginner', __( '未経験の応募', 'moegi-recruit' ), $j['inexperienced'] ? __( '可', 'moegi-recruit' ) : __( '不可（経験者）', 'moegi-recruit' ) ),
		array( 'license', __( '必要な資格', 'moegi-recruit' ), moegi_format_qualification( $j ) ),
	);
	$html  = '';
	foreach ( $items as $item ) {
		list( $icon, $label, $value ) = $item;
		$html                        .= sprintf( '<div class="is-%1$s">%2$s<dt>%3$s</dt><dd>%4$s</dd></div>', esc_attr( $icon ), moegi_icon( $icon ), esc_html( $label ), esc_html( $value ) );
	}
	$dates = sprintf(
		'<p class="job-summary__dates">%1$s <span class="num">%2$s</span>%3$s</p>',
		esc_html__( '掲載日', 'moegi-recruit' ),
		esc_html( get_the_date( 'Y.m.d', $post_id ) ),
		$j['deadline'] ? sprintf( ' ／ %1$s <span class="num">%2$s</span>', esc_html__( '掲載期限', 'moegi-recruit' ), esc_html( str_replace( '-', '.', $j['deadline'] ) ) ) : ''
	);
	return sprintf( '<div %1$s><dl class="job-summary__list">%2$s</dl>%3$s</div>', get_block_wrapper_attributes( array( 'class' => 'job-summary' ) ), $html, $dates );
}

/**
 * 給与と手当の内訳。給与の範囲、手当（1行に1項目）、賞与を表にする。
 *
 * 手当の行は「夜勤手当 1回 6,000円」のように、最初の空白より前を項目名、後を金額として扱う。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function moegi_render_job_pay( $attributes, $content, $block ) {
	$post_id = moegi_current_id( $block, 'job' );
	if ( ! $post_id ) {
		return moegi_block_placeholder( __( '給与と手当の内訳', 'moegi-recruit' ) );
	}
	$j    = moegi_get_job( $post_id );
	$rows = sprintf(
		'<tr><th scope="row">%1$s</th><td class="num">%2$s</td></tr>',
		esc_html__( '給与', 'moegi-recruit' ),
		esc_html( moegi_format_wage( $j ) )
	);
	foreach ( $j['allowances'] as $line ) {
		$parts = preg_split( '/\s+/u', $line, 2 );
		$rows .= sprintf(
			'<tr><th scope="row">%1$s</th><td>%2$s</td></tr>',
			esc_html( $parts[0] ),
			esc_html( $parts[1] ?? '' )
		);
	}
	if ( $j['bonus'] ) {
		$rows .= sprintf( '<tr><th scope="row">%1$s</th><td>%2$s</td></tr>', esc_html__( '賞与', 'moegi-recruit' ), esc_html( $j['bonus'] ) );
	}
	$remark = 'monthly' === $j['wage_unit']
		? __( '月給は基本給と毎月決まって支払う手当の合計です。夜勤手当など回数による手当は含みません。', 'moegi-recruit' )
		: __( '時給は基本の時給です。早朝・夜間などの割増は、勤務した時間に応じて加算します。', 'moegi-recruit' );
	return sprintf(
		'<div %1$s><table><caption class="screen-reader-text">%2$s</caption><tbody>%3$s</tbody></table><p class="pay__remark">%4$s</p></div>',
		get_block_wrapper_attributes( array( 'class' => 'pay' ) ),
		esc_html__( '給与と手当の内訳', 'moegi-recruit' ),
		$rows,
		esc_html( $remark )
	);
}

/**
 * 勤務時間と休日。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function moegi_render_job_schedule( $attributes, $content, $block ) {
	$post_id = moegi_current_id( $block, 'job' );
	if ( ! $post_id ) {
		return moegi_block_placeholder( __( '勤務時間と休日', 'moegi-recruit' ) );
	}
	$j = moegi_get_job( $post_id );
	return moegi_definition_list(
		array(
			__( '勤務時間', 'moegi-recruit' )  => $j['hours'],
			__( '夜勤の回数', 'moegi-recruit' ) => moegi_format_night( $j['night_shifts'] ),
			__( '年間休日', 'moegi-recruit' )  => moegi_format_holidays( $j['holidays'] ),
			__( '休日・休暇', 'moegi-recruit' ) => $j['holiday_note'],
		),
		get_block_wrapper_attributes( array( 'class' => 'spec' ) )
	);
}

/**
 * 応募の条件。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function moegi_render_job_requirement( $attributes, $content, $block ) {
	$post_id = moegi_current_id( $block, 'job' );
	if ( ! $post_id ) {
		return moegi_block_placeholder( __( '応募の条件', 'moegi-recruit' ) );
	}
	$j = moegi_get_job( $post_id );
	return moegi_definition_list(
		array(
			__( '必要な資格', 'moegi-recruit' )  => moegi_format_qualification( $j ),
			__( '未経験の応募', 'moegi-recruit' ) => $j['inexperienced'] ? __( '可。入職後の研修と、先輩職員による同行で仕事を覚えられます。', 'moegi-recruit' ) : __( '不可。同じ職種の実務経験がある方を募集しています。', 'moegi-recruit' ),
			__( '雇用形態', 'moegi-recruit' )   => implode( '、', wp_list_pluck( $j['employments'], 'name' ) ),
			__( '試用期間', 'moegi-recruit' )   => __( '3か月（条件の変更はありません）', 'moegi-recruit' ),
		),
		get_block_wrapper_attributes( array( 'class' => 'spec' ) )
	);
}

/**
 * 勤務施設（求人の詳細）。施設のイラスト、施設名、種別、所在地、交通を示し、施設の詳細へ案内する。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function moegi_render_job_facility( $attributes, $content, $block ) {
	$post_id = moegi_current_id( $block, 'job' );
	if ( ! $post_id ) {
		return moegi_block_placeholder( __( '勤務施設', 'moegi-recruit' ) );
	}
	$facility = moegi_get_job( $post_id )['facility'];
	if ( ! $facility ) {
		return sprintf( '<p %1$s>%2$s</p>', get_block_wrapper_attributes( array( 'class' => 'no-results__text' ) ), esc_html__( '勤務施設は、面接の際にご希望を伺って決めます。', 'moegi-recruit' ) );
	}
	$f = moegi_get_facility( $facility );
	return sprintf(
		'<div %1$s><div class="job-facility__art">%2$s</div><div class="job-facility__body"><p class="job-facility__kind">%3$s</p><p class="job-facility__name"><a href="%4$s">%5$s</a></p><p class="job-facility__line">%6$s%7$s</p><p class="job-facility__line">%8$s%9$s</p></div></div>',
		get_block_wrapper_attributes( array( 'class' => 'job-facility' ) ),
		moegi_facility_art_html( $facility ),
		esc_html( $f['kind'] ),
		esc_url( get_permalink( $facility ) ),
		esc_html( get_the_title( $facility ) ),
		moegi_icon( 'pin' ),
		esc_html( $f['address'] ),
		moegi_icon( 'access' ),
		esc_html( $f['access'] )
	);
}

/**
 * 応募フォームのページの URL。
 *
 * @param array $args クエリ（job_id）。
 * @return string URL。固定ページがない場合は空文字列。
 */
function moegi_entry_url( $args = array() ) {
	$page = get_page_by_path( 'entry' );
	return $page ? add_query_arg( $args, get_permalink( $page ) ) : '';
}

/**
 * 応募の導線。
 *
 * variant=side は求人の詳細の右段に置く（応募と電話のボタン。幅の狭い画面では画面下部に固定するボタンも出力する）。
 * variant=band は求人の詳細の末尾に置く帯とする。
 *
 * 募集停止・掲載期限切れの求人では、応募の導線を出さず、同じ職種の募集中の求人への導線に置き換える。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function moegi_render_job_cta( $attributes, $content, $block ) {
	$post_id = moegi_current_id( $block, 'job' );
	if ( ! $post_id ) {
		return moegi_block_placeholder( __( '応募の導線', 'moegi-recruit' ) );
	}
	$band    = 'band' === ( $attributes['variant'] ?? '' );
	$j       = moegi_get_job( $post_id );
	$contact = moegi_get_contact();

	if ( ! $j['open'] ) {
		// 同じ職種に募集中の求人がない場合は、求人一覧へ案内する。
		$type = $j['job_types'][0] ?? null;
		$type = $type && moegi_count_open_jobs( array( 'job_type' => $type->term_id ) ) ? $type : null;
		$url  = $type ? get_term_link( $type ) : get_post_type_archive_link( 'job' );
		/* translators: %s: 職種 */
		$label = $type ? sprintf( __( '募集中の%sの求人を見る', 'moegi-recruit' ), $type->name ) : __( '募集中の求人を見る', 'moegi-recruit' );
		return sprintf(
			'<div %1$s><p class="cta__closed"><strong>%2$s</strong>%3$s</p><a class="cta__primary" href="%4$s">%5$s</a></div>',
			get_block_wrapper_attributes( array( 'class' => 'cta is-closed' . ( $band ? ' is-band' : '' ) ) ),
			esc_html__( '募集停止中', 'moegi-recruit' ),
			esc_html__( 'この求人への応募は受け付けていません。', 'moegi-recruit' ),
			esc_url( is_wp_error( $url ) ? get_post_type_archive_link( 'job' ) : $url ),
			esc_html( $label )
		);
	}

	$entry = moegi_entry_url( array( 'job_id' => $post_id ) );
	$tel   = sprintf(
		'<a class="cta__tel" href="%1$s">%2$s<span>%3$s</span><span class="num">%4$s</span></a>',
		esc_url( moegi_tel_url() ),
		moegi_icon( 'tel' ),
		esc_html__( '電話で問い合わせる', 'moegi-recruit' ),
		esc_html( $contact['tel'] )
	);
	/* translators: 1: 担当部署 2: 受付時間 */
	$desk = sprintf( __( '%1$s ／ 受付 %2$s。見学だけのお申し込みもできます。', 'moegi-recruit' ), $contact['department'], $contact['hours'] );

	if ( $band ) {
		return sprintf(
			'<div %1$s><div class="cta-band__text"><p class="cta-band__title">%2$s</p><p class="cta-band__lead">%3$s</p></div><div class="cta-band__actions"><a class="cta__primary" href="%4$s">%5$s</a>%6$s</div></div>',
			get_block_wrapper_attributes( array( 'class' => 'cta is-band' ) ),
			/* translators: %s: 求人名 */
			esc_html( sprintf( __( '「%s」に応募する', 'moegi-recruit' ), get_the_title( $post_id ) ) ),
			esc_html( $desk ),
			esc_url( $entry ),
			esc_html__( '応募フォームへ進む', 'moegi-recruit' ),
			$tel
		);
	}

	return sprintf(
		'<div %1$s><a class="cta__primary" href="%2$s">%3$s</a>%4$s<p class="cta__note">%5$s</p>'
		. '<div class="cta__fixed" role="group" aria-label="%6$s"><a class="cta__tel" href="%7$s">%8$s<span>%9$s</span></a><a class="cta__primary" href="%2$s">%10$s</a></div></div>',
		get_block_wrapper_attributes( array( 'class' => 'cta' ) ),
		esc_url( $entry ),
		esc_html__( 'この求人に応募する', 'moegi-recruit' ),
		$tel,
		esc_html( $desk ),
		esc_attr__( '応募', 'moegi-recruit' ),
		esc_url( moegi_tel_url() ),
		moegi_icon( 'tel' ),
		esc_html__( '電話', 'moegi-recruit' ),
		esc_html__( '応募する', 'moegi-recruit' )
	);
}

// ------------------------------------------------------------ 施設・職員の声

/**
 * 施設のイラスト（施設の詳細）。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function moegi_render_facility_art( $attributes, $content, $block ) {
	$post_id = moegi_current_id( $block, 'facility' );
	if ( ! $post_id ) {
		return moegi_block_placeholder( __( '施設のイラスト', 'moegi-recruit' ) );
	}
	return sprintf( '<div %1$s>%2$s</div>', get_block_wrapper_attributes( array( 'class' => 'facility-art' ) ), moegi_facility_art_html( $post_id ) );
}

/**
 * 施設の概要（施設種別・定員・所在地・交通・開設年・職員数・募集中の求人数）。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function moegi_render_facility_spec( $attributes, $content, $block ) {
	$post_id = moegi_current_id( $block, 'facility' );
	if ( ! $post_id ) {
		return moegi_block_placeholder( __( '施設の概要', 'moegi-recruit' ) );
	}
	$f = moegi_get_facility( $post_id );
	return moegi_definition_list(
		array(
			__( '施設種別', 'moegi-recruit' )   => $f['kind'],
			/* translators: %d: 定員 */
			__( '定員', 'moegi-recruit' )     => $f['capacity'] ? sprintf( __( '%d名', 'moegi-recruit' ), $f['capacity'] ) : '',
			__( '所在地', 'moegi-recruit' )    => $f['address'],
			__( '交通', 'moegi-recruit' )     => $f['access'],
			/* translators: %d: 開設年 */
			__( '開設年', 'moegi-recruit' )    => $f['opened'] ? sprintf( __( '%d年', 'moegi-recruit' ), $f['opened'] ) : '',
			/* translators: %d: 職員数 */
			__( '職員数', 'moegi-recruit' )    => $f['staff'] ? sprintf( __( '%d名', 'moegi-recruit' ), $f['staff'] ) : '',
			/* translators: %d: 求人数 */
			__( '募集中の求人', 'moegi-recruit' ) => sprintf( __( '%d件', 'moegi-recruit' ), moegi_count_open_jobs( array( 'facility' => $post_id ) ) ),
		),
		get_block_wrapper_attributes( array( 'class' => 'spec' ) )
	);
}

/**
 * 職員のイラスト（職員の声の詳細）。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function moegi_render_portrait( $attributes, $content, $block ) {
	$post_id = moegi_current_id( $block, 'voice' );
	if ( ! $post_id ) {
		return moegi_block_placeholder( __( '職員のイラスト', 'moegi-recruit' ) );
	}
	return sprintf( '<div %1$s>%2$s</div>', get_block_wrapper_attributes( array( 'class' => 'portrait' ) ), moegi_portrait_html( $post_id ) );
}

/**
 * 職員のプロフィール（職種・勤務施設・入職年・入職前の経歴）。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function moegi_render_voice_profile( $attributes, $content, $block ) {
	$post_id = moegi_current_id( $block, 'voice' );
	if ( ! $post_id ) {
		return moegi_block_placeholder( __( '職員のプロフィール', 'moegi-recruit' ) );
	}
	$v = moegi_get_voice( $post_id );
	return moegi_definition_list(
		array(
			__( '職種', 'moegi-recruit' )     => implode( '、', wp_list_pluck( $v['job_types'], 'name' ) ),
			__( '勤務施設', 'moegi-recruit' )   => $v['facility'] ? get_the_title( $v['facility'] ) : '',
			/* translators: %d: 入職年 */
			__( '入職年', 'moegi-recruit' )    => $v['joined'] ? sprintf( __( '%d年', 'moegi-recruit' ), $v['joined'] ) : '',
			__( '入職前の経歴', 'moegi-recruit' ) => $v['career'],
		),
		get_block_wrapper_attributes( array( 'class' => 'spec' ) )
	);
}

/**
 * 1日の流れ。時刻を左に揃えた縦の時系列で示す。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function moegi_render_voice_day( $attributes, $content, $block ) {
	$post_id = moegi_current_id( $block, 'voice' );
	if ( ! $post_id ) {
		return moegi_block_placeholder( __( '1日の流れ', 'moegi-recruit' ) );
	}
	$day = moegi_get_voice( $post_id )['day'];
	if ( ! $day ) {
		return '';
	}
	$html = '';
	foreach ( $day as $step ) {
		$html .= sprintf(
			'<li>%1$s<span class="day__text">%2$s</span></li>',
			$step[0] ? sprintf( '<time class="day__time num">%s</time>', esc_html( $step[0] ) ) : '<span class="day__time"></span>',
			esc_html( $step[1] )
		);
	}
	return sprintf( '<ol %1$s>%2$s</ol>', get_block_wrapper_attributes( array( 'class' => 'day' ) ), $html );
}

// ------------------------------------------------------------ トップ・採用窓口・応募

/**
 * 職種から探す（介護職・看護職・その他の職種の入口）。職種ごとに募集中の求人数を示す。
 *
 * 介護職と看護職は大きな入口とし、それ以外の職種は「その他の職種」にまとめて並べる。
 *
 * @return string HTML。
 */
function moegi_render_type_entry() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'job_type',
			'hide_empty' => false,
			'orderby'    => 'term_id',
		)
	);
	$main  = '';
	$other = '';
	foreach ( $terms as $term ) {
		$count = moegi_count_open_jobs( array( 'job_type' => $term->term_id ) );
		if ( in_array( $term->slug, array( 'care-worker', 'nurse' ), true ) ) {
			$main .= sprintf(
				'<li class="type-entry__main"><a href="%1$s">%2$s<span class="type-entry__name">%3$s</span><span class="type-entry__desc">%4$s</span><span class="type-entry__count"><span class="num">%5$d</span>%6$s</span></a></li>',
				esc_url( get_term_link( $term ) ),
				moegi_icon( 'type-' . $term->slug ),
				esc_html( $term->name ),
				esc_html( $term->description ),
				$count,
				esc_html__( '件', 'moegi-recruit' )
			);
			continue;
		}
		$other .= sprintf(
			'<li><a href="%1$s"><span>%2$s</span><span class="type-entry__mini"><span class="num">%3$d</span>%4$s</span></a></li>',
			esc_url( get_term_link( $term ) ),
			esc_html( $term->name ),
			$count,
			esc_html__( '件', 'moegi-recruit' )
		);
	}
	$other_html = $other ? sprintf(
		'<li class="type-entry__other"><p class="type-entry__other-head">%1$s<span class="type-entry__name">%2$s</span></p><ul>%3$s</ul></li>',
		moegi_icon( 'type-other' ),
		esc_html__( 'その他の職種', 'moegi-recruit' ),
		$other
	) : '';
	return sprintf( '<ul %1$s>%2$s%3$s</ul>', get_block_wrapper_attributes( array( 'class' => 'type-entry' ) ), $main, $other_html );
}

/**
 * 採用窓口の定義リスト（担当部署・電話番号・受付時間）。
 *
 * @return string HTML。
 */
function moegi_render_contact_info() {
	$contact = moegi_get_contact();
	$labels  = moegi_contact_labels();
	return moegi_definition_list(
		array(
			$labels['department'] => $contact['department'],
			$labels['tel']        => $contact['tel'],
			$labels['hours']      => $contact['hours'],
		),
		get_block_wrapper_attributes( array( 'class' => 'spec' ) )
	);
}

/**
 * 見学会の日程。採用窓口の設定の「見学会の日程」を1行1件で並べ、申し込みの方法を添える。
 *
 * @return string HTML。
 */
function moegi_render_tour() {
	$contact = moegi_get_contact();
	$lines   = moegi_lines( $contact['tour'] );
	$items   = '';
	foreach ( $lines as $line ) {
		$items .= sprintf( '<li>%1$s<span>%2$s</span></li>', moegi_icon( 'calendar' ), esc_html( $line ) );
	}
	$list = $items ? sprintf( '<ul class="tour__list">%s</ul>', $items ) : sprintf( '<p class="no-results__text">%s</p>', esc_html__( '次回の見学会の日程は、決まりしだいお知らせします。個別の見学はいつでも承ります。', 'moegi-recruit' ) );
	return sprintf(
		'<div %1$s>%2$s<p class="tour__how">%3$s<a class="tour__tel num" href="%4$s">%5$s</a><span class="tour__desk">%6$s</span></p></div>',
		get_block_wrapper_attributes( array( 'class' => 'tour' ) ),
		$list,
		esc_html__( '見学会のお申し込み・個別の見学のご相談は', 'moegi-recruit' ),
		esc_url( moegi_tel_url() ),
		esc_html( $contact['tel'] ),
		/* translators: 1: 担当部署 2: 受付時間 */
		esc_html( sprintf( __( '%1$s（受付 %2$s）', 'moegi-recruit' ), $contact['department'], $contact['hours'] ) )
	);
}

/**
 * 応募フォーム（表示のみ）。
 *
 * 求人の詳細から来た場合（job_id）は、その求人を選択した状態とする。選択肢は募集中の求人に限る。
 *
 * @return string HTML。
 */
function moegi_render_entry_form() {
	$job_id = absint( get_query_var( 'job_id' ) );
	$job_id = $job_id && 'job' === get_post_type( $job_id ) && 'publish' === get_post_status( $job_id ) && moegi_get_job( $job_id )['open'] ? $job_id : 0;
	$uid    = wp_unique_id( 'moegi-entry-' );

	$options = array( '' => __( '見学・相談のみ（求人を選ばない）', 'moegi-recruit' ) );
	foreach ( moegi_open_jobs() as $post ) {
		$j                    = moegi_get_job( $post->ID );
		$place                = $j['facility'] ? ' ／ ' . get_the_title( $j['facility'] ) : '';
		$options[ $post->ID ] = get_the_title( $post ) . $place;
	}

	$field  = static function ( $name, $label, $control, $required = false ) use ( $uid ) {
		return sprintf(
			'<div class="form__field"><label for="%1$s-%2$s">%3$s%4$s</label>%5$s</div>',
			esc_attr( $uid ),
			esc_attr( $name ),
			esc_html( $label ),
			$required ? '<span class="form__req">' . esc_html__( '必須', 'moegi-recruit' ) . '</span>' : '',
			$control
		);
	};
	$input  = static function ( $name, $type, $autocomplete, $required = false ) use ( $uid ) {
		return sprintf( '<input id="%1$s-%2$s" name="%2$s" type="%3$s" autocomplete="%4$s"%5$s>', esc_attr( $uid ), esc_attr( $name ), esc_attr( $type ), esc_attr( $autocomplete ), $required ? ' required' : '' );
	};
	$select = static function ( $name, $options, $selected ) use ( $uid ) {
		return sprintf( '<select id="%1$s-%2$s" name="%2$s">%3$s</select>', esc_attr( $uid ), esc_attr( $name ), moegi_options_html( $options, $selected ) );
	};

	$html  = $field( 'job', __( '応募する求人', 'moegi-recruit' ), $select( 'job', $options, $job_id ? $job_id : '' ) );
	$html .= $field(
		'purpose',
		__( 'お申し込みの内容', 'moegi-recruit' ),
		$select(
			'purpose',
			array(
				'apply' => __( '応募する', 'moegi-recruit' ),
				'tour'  => __( '見学してから決めたい', 'moegi-recruit' ),
				'ask'   => __( '話を聞きたい', 'moegi-recruit' ),
			),
			'apply'
		)
	);
	$html .= $field( 'name', __( 'お名前', 'moegi-recruit' ), $input( 'name', 'text', 'name', true ), true );
	$html .= $field( 'kana', __( 'ふりがな', 'moegi-recruit' ), $input( 'kana', 'text', 'off', true ), true );
	$html .= $field( 'email', __( 'メールアドレス', 'moegi-recruit' ), $input( 'email', 'email', 'email', true ), true );
	$html .= $field( 'tel', __( '電話番号', 'moegi-recruit' ), $input( 'tel', 'tel', 'tel', true ), true );
	$html .= $field(
		'license',
		__( 'お持ちの資格', 'moegi-recruit' ),
		$select( 'license', moegi_qualification_options(), 'none' )
	);
	$html .= $field( 'start', __( '入職の希望時期', 'moegi-recruit' ), $input( 'start', 'text', 'off' ) );
	$html .= $field( 'message', __( 'ご質問・ご希望の勤務条件など', 'moegi-recruit' ), sprintf( '<textarea id="%1$s-message" name="message" rows="5"></textarea>', esc_attr( $uid ) ) );

	$selected = $job_id ? sprintf(
		'<p class="form__selected"><span>%1$s</span><strong>%2$s</strong></p>',
		esc_html__( '選択中の求人', 'moegi-recruit' ),
		esc_html( get_the_title( $job_id ) )
	) : '';

	return sprintf(
		'<div %1$s><p class="form__notice" id="%2$s-notice">%3$s</p>%4$s<form class="form" action="#" method="post" aria-describedby="%2$s-notice">%5$s<button type="submit" disabled>%6$s</button></form></div>',
		get_block_wrapper_attributes(),
		esc_attr( $uid ),
		esc_html__( 'このフォームは制作サンプルのため表示のみです。送信はできません。', 'moegi-recruit' ),
		$selected,
		$html,
		esc_html__( '内容を確認して送信する', 'moegi-recruit' )
	);
}
