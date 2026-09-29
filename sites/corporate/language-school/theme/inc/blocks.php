<?php
/**
 * 動的ブロック
 *
 * コース・講師の値の組み合わせで表示が決まる部分（コースカード、絞り込み、件数と並べ替え、主要項目、費用の内訳、
 * 体験レッスンの導線など）をサーバー側で描画するブロックとして登録する。エディターでは assets/js/editor.js が
 * 同じブロックを登録し、サーバー側の描画結果を表示する。
 *
 * @package hibiki-english
 */

/**
 * テーマのブロックの定義。
 *
 * @return array<string, array> ブロック名と登録の引数。
 */
function hibiki_block_definitions() {
	$level = array(
		'level' => array(
			'type'    => 'integer',
			'default' => 3,
		),
	);
	return array(
		'hibiki/course-card'        => array(
			'title'           => __( 'コースカード', 'hibiki-english' ),
			'attributes'      => $level,
			'uses_context'    => array( 'postId' ),
			'render_callback' => 'hibiki_render_course_card',
		),
		'hibiki/instructor-card'    => array(
			'title'           => __( '講師カード', 'hibiki-english' ),
			'attributes'      => $level,
			'uses_context'    => array( 'postId' ),
			'render_callback' => 'hibiki_render_instructor_card',
		),
		'hibiki/portrait'           => array(
			'title'           => __( '講師のイラスト', 'hibiki-english' ),
			'uses_context'    => array( 'postId' ),
			'render_callback' => 'hibiki_render_portrait',
		),
		'hibiki/instructor-profile' => array(
			'title'           => __( '講師のプロフィール項目', 'hibiki-english' ),
			'uses_context'    => array( 'postId' ),
			'render_callback' => 'hibiki_render_instructor_profile',
		),
		'hibiki/course-filter'      => array(
			'title'           => __( 'コースの絞り込み', 'hibiki-english' ),
			'render_callback' => 'hibiki_render_course_filter',
		),
		'hibiki/result-bar'         => array(
			'title'           => __( '件数・条件・並べ替え', 'hibiki-english' ),
			'render_callback' => 'hibiki_render_result_bar',
		),
		'hibiki/course-head'        => array(
			'title'           => __( 'コースの募集状況と札', 'hibiki-english' ),
			'uses_context'    => array( 'postId' ),
			'render_callback' => 'hibiki_render_course_head',
		),
		'hibiki/course-price'       => array(
			'title'           => __( 'コースの月謝', 'hibiki-english' ),
			'uses_context'    => array( 'postId' ),
			'render_callback' => 'hibiki_render_course_price',
		),
		'hibiki/course-summary'     => array(
			'title'           => __( 'コースの主要項目', 'hibiki-english' ),
			'uses_context'    => array( 'postId' ),
			'render_callback' => 'hibiki_render_course_summary',
		),
		'hibiki/course-flow'        => array(
			'title'           => __( '1回のレッスンの流れ', 'hibiki-english' ),
			'uses_context'    => array( 'postId' ),
			'render_callback' => 'hibiki_render_course_flow',
		),
		'hibiki/course-cost'        => array(
			'title'           => __( '月謝と費用の内訳', 'hibiki-english' ),
			'uses_context'    => array( 'postId' ),
			'render_callback' => 'hibiki_render_course_cost',
		),
		'hibiki/course-instructors' => array(
			'title'           => __( '担当講師', 'hibiki-english' ),
			'uses_context'    => array( 'postId' ),
			'render_callback' => 'hibiki_render_course_instructors',
		),
		'hibiki/course-cta'         => array(
			'title'           => __( '体験レッスンの導線', 'hibiki-english' ),
			'uses_context'    => array( 'postId' ),
			'render_callback' => 'hibiki_render_course_cta',
		),
		'hibiki/target-entry'       => array(
			'title'           => __( '対象から選ぶ', 'hibiki-english' ),
			'render_callback' => 'hibiki_render_target_entry',
		),
		'hibiki/school-info'        => array(
			'title'           => __( '教室情報', 'hibiki-english' ),
			'render_callback' => 'hibiki_render_school_info',
		),
		'hibiki/trial-form'         => array(
			'title'           => __( '体験レッスンの申し込みフォーム（表示のみ）', 'hibiki-english' ),
			'render_callback' => 'hibiki_render_trial_form',
		),
	);
}

/**
 * ブロックを登録する。
 */
function hibiki_register_blocks() {
	foreach ( hibiki_block_definitions() as $name => $args ) {
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
add_action( 'init', 'hibiki_register_blocks' );

/**
 * 体験レッスンの申し込みで、対象のコースを受け取るクエリ変数を登録する。
 *
 * course はコースの投稿タイプのクエリ変数と重なるため、course_id とする。
 *
 * @param string[] $vars 公開クエリ変数。
 * @return string[] 追加後の公開クエリ変数。
 */
function hibiki_trial_query_vars( $vars ) {
	$vars[] = 'course_id';
	return $vars;
}
add_filter( 'query_vars', 'hibiki_trial_query_vars' );

/**
 * テーマ同梱の SVG アイコンを返す。装飾として扱い、支援技術からは隠す。
 *
 * @param string $name アイコン名（assets/icons/<name>.svg）。
 * @return string SVG。
 */
function hibiki_icon( $name ) {
	return hibiki_svg( 'icons/' . sanitize_file_name( $name ), 'class="icon" aria-hidden="true" focusable="false"' );
}

/**
 * テーマ同梱の SVG を読み、ルート要素に属性を加えて返す。
 *
 * @param string $path       assets/ からのパス（拡張子を除く）。
 * @param string $attributes ルート要素に加える属性。
 * @return string SVG。ファイルがない場合は空文字列。
 */
function hibiki_svg( $path, $attributes ) {
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
function hibiki_current_id( $block, $post_type ) {
	$post_id = (int) ( $block->context['postId'] ?? get_the_ID() );
	return $post_id && get_post_type( $post_id ) === $post_type ? $post_id : 0;
}

/**
 * エディターで対象の投稿がない場合の表示。
 *
 * @param string $label ブロックの名前。
 * @return string HTML。
 */
function hibiki_block_placeholder( $label ) {
	return sprintf( '<div %s><p>%s</p></div>', get_block_wrapper_attributes( array( 'class' => 'hibiki-placeholder' ) ), esc_html( $label ) );
}

/**
 * 見出しの階層を 2〜4 に収める。
 *
 * @param array $attributes 属性。
 * @return int 見出しの階層。
 */
function hibiki_heading_level( $attributes ) {
	return min( 4, max( 2, (int) ( $attributes['level'] ?? 3 ) ) );
}

/**
 * 募集状況の札。
 *
 * @param array $c コースの値（hibiki_get_course() の戻り値）。
 * @return string HTML。
 */
function hibiki_status_badge( $c ) {
	return sprintf( '<span class="status-badge is-%1$s">%2$s</span>', esc_attr( $c['status'] ), esc_html( $c['status_label'] ) );
}

/**
 * 対象・形式の札。
 *
 * @param array $c コースの値。
 * @return string HTML。
 */
function hibiki_course_tags( $c ) {
	$html = '';
	foreach ( $c['targets'] as $term ) {
		$html .= sprintf( '<span class="tag is-target">%s</span>', esc_html( $term->name ) );
	}
	if ( $c['format_label'] ) {
		$html .= sprintf( '<span class="tag is-format">%1$s%2$s</span>', hibiki_icon( 'format-' . $c['format'] ), esc_html( $c['format_label'] ) );
	}
	return $html;
}

/**
 * 担当講師の名前（読点区切り）。
 *
 * @param array $c コースの値。
 * @return string 講師名。
 */
function hibiki_instructor_names( $c ) {
	return implode( '、', array_map( 'get_the_title', $c['instructors'] ) );
}

/**
 * コースカード。
 *
 * 上段に対象と形式の札、コース名、レベル、中段に月謝（最も大きく）と1回あたりの金額、下段に開講曜日と時間帯、
 * 担当講師を置き、募集状況の札を右上に付ける。コース名のリンクを CSS でカード全体へ広げる。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function hibiki_render_course_card( $attributes, $content, $block ) {
	$post_id = hibiki_current_id( $block, 'course' );
	if ( ! $post_id ) {
		return hibiki_block_placeholder( __( 'コースカード', 'hibiki-english' ) );
	}
	$c     = hibiki_get_course( $post_id );
	$level = hibiki_heading_level( $attributes );
	$note  = 'full' === $c['status'] ? sprintf( '<p class="course-card__note">%s</p>', esc_html__( '満席のため、キャンセル待ちを受け付けています', 'hibiki-english' ) ) : '';

	return sprintf(
		'<article %1$s>%2$s<div class="course-card__head"><p class="course-card__tags">%3$s</p><h%4$d class="course-card__title"><a href="%5$s">%6$s</a></h%4$d><p class="course-card__level">%7$s</p></div>'
		. '<div class="course-card__price"><p class="fee"><span class="fee__label">%8$s</span><span class="fee__num num">%9$s</span><span class="fee__unit">%10$s</span></p><p class="per">%11$s</p></div>'
		. '<dl class="course-card__meta"><div>%12$s<dt>%13$s</dt><dd>%14$s</dd></div><div>%15$s<dt>%16$s</dt><dd>%17$s</dd></div></dl>%18$s</article>',
		get_block_wrapper_attributes( array( 'class' => 'course-card is-' . $c['status'] ) ),
		hibiki_status_badge( $c ),
		hibiki_course_tags( $c ),
		$level,
		esc_url( get_permalink( $post_id ) ),
		esc_html( get_the_title( $post_id ) ),
		/* translators: %s: レベル */
		esc_html( sprintf( __( 'レベル：%s', 'hibiki-english' ), $c['level_label'] ) ),
		esc_html__( '月謝', 'hibiki-english' ),
		esc_html( number_format( $c['fee'] ) ),
		esc_html__( '円（税込）', 'hibiki-english' ),
		/* translators: 1: 1回あたりの金額 2: 月の回数 3: 1回の時間 */
		esc_html( sprintf( __( '1回あたり %1$s（月%2$d回・%3$d分）', 'hibiki-english' ), hibiki_format_yen( $c['per_lesson'] ), $c['times'], $c['minutes'] ) ),
		hibiki_icon( 'calendar' ),
		esc_html__( '開講', 'hibiki-english' ),
		esc_html( $c['schedule'] ),
		hibiki_icon( 'person' ),
		esc_html__( '講師', 'hibiki-english' ),
		esc_html( hibiki_instructor_names( $c ) ),
		$note
	);
}

/**
 * 講師のイラスト。アイキャッチ画像がある場合はそれを、ない場合はテーマに同梱した SVG を表示する。
 *
 * @param int $post_id 講師の投稿 ID。
 * @return string HTML。
 */
function hibiki_portrait_html( $post_id ) {
	if ( has_post_thumbnail( $post_id ) ) {
		return get_the_post_thumbnail( $post_id, 'medium', array( 'class' => 'portrait__img' ) );
	}
	$key = hibiki_get_instructor( $post_id )['portrait'];
	/* translators: %s: 講師名 */
	$label = sprintf( __( '%s のイラスト', 'hibiki-english' ), get_the_title( $post_id ) );
	$svg   = $key ? hibiki_svg( 'portraits/' . sanitize_file_name( $key ), sprintf( 'class="portrait__svg" role="img" aria-label="%s"', esc_attr( $label ) ) ) : '';
	return $svg ? $svg : hibiki_svg( 'portraits/none', 'class="portrait__svg" aria-hidden="true" focusable="false"' );
}

/**
 * 講師カード。イラスト、名前、担当言語と指導歴、得意分野を置く。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function hibiki_render_instructor_card( $attributes, $content, $block ) {
	$post_id = hibiki_current_id( $block, 'instructor' );
	if ( ! $post_id ) {
		return hibiki_block_placeholder( __( '講師カード', 'hibiki-english' ) );
	}
	return hibiki_instructor_card_html( $post_id, hibiki_heading_level( $attributes ), get_block_wrapper_attributes( array( 'class' => 'instructor-card' ) ) );
}

/**
 * 講師カードの HTML。
 *
 * @param int    $post_id 講師の投稿 ID。
 * @param int    $level   見出しの階層。
 * @param string $wrapper ルート要素の属性。
 * @return string HTML。
 */
function hibiki_instructor_card_html( $post_id, $level, $wrapper = 'class="instructor-card"' ) {
	$i = hibiki_get_instructor( $post_id );
	return sprintf(
		'<article %1$s><div class="portrait">%2$s</div><div class="instructor-card__body"><h%3$d class="instructor-card__name"><a href="%4$s">%5$s</a></h%3$d><p class="instructor-card__meta">%6$s</p><p class="instructor-card__specialty">%7$s</p></div></article>',
		$wrapper,
		hibiki_portrait_html( $post_id ),
		$level,
		esc_url( get_permalink( $post_id ) ),
		esc_html( get_the_title( $post_id ) ),
		/* translators: 1: 担当言語 2: 指導歴（年） */
		esc_html( sprintf( __( '%1$s ／ 指導歴 %2$d年', 'hibiki-english' ), $i['language'], $i['years'] ) ),
		esc_html( $i['specialty'] )
	);
}

/**
 * 講師のイラスト（講師の詳細）。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function hibiki_render_portrait( $attributes, $content, $block ) {
	$post_id = hibiki_current_id( $block, 'instructor' );
	if ( ! $post_id ) {
		return hibiki_block_placeholder( __( '講師のイラスト', 'hibiki-english' ) );
	}
	return sprintf( '<div %1$s>%2$s</div>', get_block_wrapper_attributes( array( 'class' => 'portrait' ) ), hibiki_portrait_html( $post_id ) );
}

/**
 * 講師のプロフィール項目（担当言語・指導歴・得意分野・保有資格）。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function hibiki_render_instructor_profile( $attributes, $content, $block ) {
	$post_id = hibiki_current_id( $block, 'instructor' );
	if ( ! $post_id ) {
		return hibiki_block_placeholder( __( '講師のプロフィール項目', 'hibiki-english' ) );
	}
	$i    = hibiki_get_instructor( $post_id );
	$rows = array(
		__( '担当言語', 'hibiki-english' ) => $i['language'],
		/* translators: %d: 指導歴（年） */
		__( '指導歴', 'hibiki-english' )  => sprintf( __( '%d年', 'hibiki-english' ), $i['years'] ),
		__( '得意分野', 'hibiki-english' ) => $i['specialty'],
		__( '保有資格', 'hibiki-english' ) => $i['qualifications'],
	);
	return hibiki_definition_list( $rows, get_block_wrapper_attributes( array( 'class' => 'spec' ) ) );
}

/**
 * 2列の定義リスト。値が空の行は出力しない。
 *
 * @param array<string, string> $rows    見出しと値。
 * @param string                $wrapper ルート要素の属性。
 * @return string HTML。
 */
function hibiki_definition_list( $rows, $wrapper ) {
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
 * 選択肢のラジオボタンの組。
 *
 * @param string $name     クエリ変数の名前。
 * @param string $legend   組の見出し。
 * @param array  $options  値と表示名。値が空文字列の選択肢を「指定なし」とする。
 * @param string $selected 選択中の値。
 * @param string $uid      id の接頭辞。
 * @return string HTML。
 */
function hibiki_radio_group( $name, $legend, $options, $selected, $uid ) {
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
 * 分類の語を「指定なし」付きの選択肢として返す。
 *
 * @param string $taxonomy 分類。
 * @return array<string, string> スラッグと名前。
 */
function hibiki_term_options( $taxonomy ) {
	$list = array( '' => __( '指定なし', 'hibiki-english' ) );
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
 * コースの絞り込み（コース一覧の左段）。対象・目的・形式・月謝の上限を選ぶ。
 *
 * 対象別の一覧では対象が URL で決まるため、対象の選択肢を出さず、送信先を対象別の一覧とする。
 * 幅の狭い画面では assets/js/front.js が折りたたむ。スクリプトが動かない場合は開いたまま表示する。
 *
 * @return string HTML。
 */
function hibiki_render_course_filter() {
	$filters = hibiki_get_filters();
	$uid     = wp_unique_id( 'hibiki-filter-' );
	$term    = get_queried_object();
	$on_term = $term instanceof WP_Term && 'target' === $term->taxonomy;

	$fee = array( '' => __( '指定なし', 'hibiki-english' ) );
	foreach ( hibiki_fee_max_options() as $yen ) {
		/* translators: %s: 金額 */
		$fee[ $yen ] = sprintf( __( '%s以下', 'hibiki-english' ), hibiki_format_yen( $yen ) );
	}

	$fields = '';
	if ( ! $on_term ) {
		$fields .= hibiki_radio_group( 'target', __( '対象', 'hibiki-english' ), hibiki_term_options( 'target' ), $filters['target'], $uid );
	}
	$fields .= hibiki_radio_group( 'purpose', __( '目的', 'hibiki-english' ), hibiki_term_options( 'purpose' ), $filters['purpose'], $uid );
	$fields .= hibiki_radio_group( 'format', __( '形式', 'hibiki-english' ), array_merge( array( '' => __( '指定なし', 'hibiki-english' ) ), hibiki_format_options() ), $filters['format'], $uid );
	$fields .= sprintf(
		'<div class="filter__group"><label class="filter__label" for="%1$s-fee">%2$s</label><select id="%1$s-fee" name="fee_max">%3$s</select></div>',
		esc_attr( $uid ),
		esc_html__( '月謝の上限（税込）', 'hibiki-english' ),
		hibiki_options_html( $fee, $filters['fee_max'] ? $filters['fee_max'] : '' )
	);
	if ( 'recommended' !== $filters['sort'] ) {
		$fields .= sprintf( '<input type="hidden" name="sort" value="%s">', esc_attr( $filters['sort'] ) );
	}

	$form = sprintf(
		'<form class="filter__form" action="%1$s" method="get" role="search" aria-label="%2$s">%3$s<button type="submit" class="filter__submit">%4$s</button></form>',
		esc_url( $on_term ? get_term_link( $term ) : get_post_type_archive_link( 'course' ) ),
		esc_attr__( 'コースの絞り込み', 'hibiki-english' ),
		$fields,
		esc_html__( 'この条件で探す', 'hibiki-english' )
	);

	return sprintf(
		'<div %1$s><details class="filter__details" open><summary>%2$s</summary>%3$s</details></div>',
		get_block_wrapper_attributes(),
		esc_html__( '条件で絞り込む', 'hibiki-english' ),
		$form
	);
}

/**
 * 選択肢の option 要素を返す。
 *
 * @param array  $options  値と表示名。
 * @param string $selected 選択中の値。
 * @return string HTML。
 */
function hibiki_options_html( $options, $selected ) {
	$html = '';
	foreach ( $options as $value => $label ) {
		$html .= sprintf( '<option value="%s"%s>%s</option>', esc_attr( $value ), selected( (string) $value, (string) $selected, false ), esc_html( $label ) );
	}
	return $html;
}

/**
 * 件数・選択中の条件（解除できるチップ）・並べ替え。
 *
 * @return string HTML。
 */
function hibiki_render_result_bar() {
	global $wp_query;
	$filters = hibiki_get_filters();
	$term    = get_queried_object();
	$on_term = $term instanceof WP_Term && 'target' === $term->taxonomy;
	$base    = $on_term ? get_term_link( $term ) : get_post_type_archive_link( 'course' );
	if ( $on_term ) {
		$filters['target'] = '';
	}

	$url = static function ( $changes ) use ( $filters, $base ) {
		$next = array_merge( $filters, $changes );
		$args = array_filter(
			array(
				'target'  => $next['target'],
				'purpose' => $next['purpose'],
				'format'  => $next['format'],
				'fee_max' => $next['fee_max'],
				'sort'    => 'recommended' === $next['sort'] ? '' : $next['sort'],
			)
		);
		return esc_url( add_query_arg( $args, $base ) );
	};

	$chips = array();
	foreach ( array(
		'target'  => __( '対象', 'hibiki-english' ),
		'purpose' => __( '目的', 'hibiki-english' ),
	) as $taxonomy => $label ) {
		if ( $filters[ $taxonomy ] ) {
			$t       = get_term_by( 'slug', $filters[ $taxonomy ], $taxonomy );
			$chips[] = array( $label, $t ? $t->name : $filters[ $taxonomy ], array( $taxonomy => '' ) );
		}
	}
	if ( $filters['format'] ) {
		$chips[] = array( __( '形式', 'hibiki-english' ), hibiki_format_options()[ $filters['format'] ], array( 'format' => '' ) );
	}
	if ( $filters['fee_max'] ) {
		/* translators: %s: 金額 */
		$chips[] = array( __( '月謝', 'hibiki-english' ), sprintf( __( '%s以下', 'hibiki-english' ), hibiki_format_yen( $filters['fee_max'] ) ), array( 'fee_max' => 0 ) );
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
				esc_attr( sprintf( __( '条件を解除: %1$s %2$s', 'hibiki-english' ), $label, $value ) ),
				esc_html( $label ),
				esc_html( $value )
			);
		}
		$items     .= sprintf(
			'<li><a class="chip-clear" href="%1$s">%2$s</a></li>',
			$url(
				array(
					'target'  => '',
					'purpose' => '',
					'format'  => '',
					'fee_max' => 0,
				)
			),
			esc_html__( 'すべて解除', 'hibiki-english' )
		);
		$chips_html = sprintf( '<ul class="result-bar__chips" aria-label="%1$s">%2$s</ul>', esc_attr__( '選択中の条件', 'hibiki-english' ), $items );
	}

	$sort = '';
	foreach ( hibiki_sort_options() as $key => $label ) {
		$sort .= sprintf(
			'<li><a href="%1$s"%2$s>%3$s</a></li>',
			$url( array( 'sort' => $key ) ),
			$key === $filters['sort'] ? ' aria-current="true"' : '',
			esc_html( $label )
		);
	}

	return sprintf(
		'<div %1$s><div class="result-bar__row"><p class="result-bar__count" role="status"><span class="num">%2$s</span>%3$s</p><nav class="result-bar__sort" aria-label="%4$s"><ul>%5$s</ul></nav></div>%6$s</div>',
		get_block_wrapper_attributes(),
		esc_html( number_format( (int) $wp_query->found_posts ) ),
		esc_html__( '件のコース', 'hibiki-english' ),
		esc_attr__( '並べ替え', 'hibiki-english' ),
		$sort,
		$chips_html
	);
}

/**
 * コースの詳細の冒頭に置く募集状況と札（対象・形式・レベル）。開講準備中のコースでは、その旨の案内を加える。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function hibiki_render_course_head( $attributes, $content, $block ) {
	$post_id = hibiki_current_id( $block, 'course' );
	if ( ! $post_id ) {
		return hibiki_block_placeholder( __( 'コースの募集状況と札', 'hibiki-english' ) );
	}
	$c      = hibiki_get_course( $post_id );
	$notice = '';
	if ( 'preparing' === $c['status'] ) {
		$notice = sprintf(
			'<p class="course-head__notice"><strong>%1$s</strong>%2$s</p>',
			esc_html__( 'このコースは開講準備中です。', 'hibiki-english' ),
			esc_html__( '内容・日程・月謝は予定であり、変わる場合があります。開講が決まりしだい、このページでお知らせします。', 'hibiki-english' )
		);
	}
	return sprintf(
		'<div %1$s><p class="course-head__tags">%2$s%3$s<span class="tag is-level">%4$s</span></p>%5$s</div>',
		get_block_wrapper_attributes( array( 'class' => 'course-head' ) ),
		hibiki_status_badge( $c ),
		hibiki_course_tags( $c ),
		/* translators: %s: レベル */
		esc_html( sprintf( __( 'レベル：%s', 'hibiki-english' ), $c['level_label'] ) ),
		$notice
	);
}

/**
 * コースの月謝（詳細の右段）。月謝を最も大きく示し、1回あたりの金額を併記する。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function hibiki_render_course_price( $attributes, $content, $block ) {
	$post_id = hibiki_current_id( $block, 'course' );
	if ( ! $post_id ) {
		return hibiki_block_placeholder( __( 'コースの月謝', 'hibiki-english' ) );
	}
	$c = hibiki_get_course( $post_id );
	return sprintf(
		'<div %1$s><p class="fee"><span class="fee__label">%2$s</span><span class="fee__num num">%3$s</span><span class="fee__unit">%4$s</span></p><p class="per">%5$s</p></div>',
		get_block_wrapper_attributes( array( 'class' => 'course-price' ) ),
		esc_html__( '月謝', 'hibiki-english' ),
		esc_html( number_format( $c['fee'] ) ),
		esc_html__( '円（税込）', 'hibiki-english' ),
		/* translators: 1: 1回あたりの金額 2: 月の回数 */
		esc_html( sprintf( __( '1回あたり %1$s（月%2$d回）', 'hibiki-english' ), hibiki_format_yen( $c['per_lesson'] ), $c['times'] ) )
	);
}

/**
 * コースの主要項目（形式・時間・回数・定員・開講曜日と時間帯・レベル）。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function hibiki_render_course_summary( $attributes, $content, $block ) {
	$post_id = hibiki_current_id( $block, 'course' );
	if ( ! $post_id ) {
		return hibiki_block_placeholder( __( 'コースの主要項目', 'hibiki-english' ) );
	}
	$c     = hibiki_get_course( $post_id );
	$items = array(
		array( 'format-' . $c['format'], __( '形式', 'hibiki-english' ), $c['format_label'], '' ),
		/* translators: %d: 分 */
		array( 'clock', __( '1回の時間', 'hibiki-english' ), sprintf( __( '%d分', 'hibiki-english' ), $c['minutes'] ), 'num' ),
		/* translators: %d: 回数 */
		array( 'repeat', __( '月の回数', 'hibiki-english' ), sprintf( __( '%d回', 'hibiki-english' ), $c['times'] ), 'num' ),
		/* translators: %d: 定員 */
		array( 'people', __( '定員', 'hibiki-english' ), sprintf( __( '%d名', 'hibiki-english' ), $c['capacity'] ), 'num' ),
		array( 'level', __( 'レベル', 'hibiki-english' ), $c['level_label'], '' ),
		array( 'calendar', __( '開講曜日と時間帯', 'hibiki-english' ), $c['schedule'], '' ),
	);
	$html = '';
	foreach ( $items as $item ) {
		list( $icon, $label, $value, $class ) = $item;
		$html                                .= sprintf(
			'<div>%1$s<dt>%2$s</dt><dd%3$s>%4$s</dd></div>',
			hibiki_icon( $icon ),
			esc_html( $label ),
			$class ? ' class="num"' : '',
			esc_html( $value )
		);
	}
	return sprintf( '<dl %1$s>%2$s</dl>', get_block_wrapper_attributes( array( 'class' => 'course-summary' ) ), $html );
}

/**
 * 1回のレッスンの流れ。入力欄の1行を1つの手順とする。「内容（10分）」の形で書くと、時間を右に揃えて示す。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function hibiki_render_course_flow( $attributes, $content, $block ) {
	$post_id = hibiki_current_id( $block, 'course' );
	if ( ! $post_id ) {
		return hibiki_block_placeholder( __( '1回のレッスンの流れ', 'hibiki-english' ) );
	}
	$steps = hibiki_get_course( $post_id )['flow'];
	if ( ! $steps ) {
		return '';
	}
	$html = '';
	foreach ( $steps as $step ) {
		$time = '';
		if ( preg_match( '/^(.*?)[（(]\s*(\d+)\s*分\s*[)）]$/u', $step, $m ) ) {
			$step = $m[1];
			/* translators: %d: 分 */
			$time = sprintf( '<span class="flow__time num">%s</span>', esc_html( sprintf( __( '%d分', 'hibiki-english' ), (int) $m[2] ) ) );
		}
		$html .= sprintf( '<li><span class="flow__text">%1$s</span>%2$s</li>', esc_html( $step ), $time );
	}
	return sprintf( '<ol %1$s>%2$s</ol>', get_block_wrapper_attributes( array( 'class' => 'flow' ) ), $html );
}

/**
 * 月謝と費用の内訳。入会金・月謝・1回あたりの金額・初月の合計を示す。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function hibiki_render_course_cost( $attributes, $content, $block ) {
	$post_id = hibiki_current_id( $block, 'course' );
	if ( ! $post_id ) {
		return hibiki_block_placeholder( __( '月謝と費用の内訳', 'hibiki-english' ) );
	}
	$c    = hibiki_get_course( $post_id );
	$rows = array(
		array( __( '入会金', 'hibiki-english' ), __( '入会時のみ', 'hibiki-english' ), $c['entry_fee'] ? hibiki_format_yen( $c['entry_fee'] ) : __( 'なし', 'hibiki-english' ) ),
		/* translators: 1: 月の回数 2: 1回の時間 */
		array( __( '月謝', 'hibiki-english' ), sprintf( __( '月%1$d回・1回%2$d分', 'hibiki-english' ), $c['times'], $c['minutes'] ), hibiki_format_yen( $c['fee'] ) ),
		array( __( '1回あたり', 'hibiki-english' ), __( '月謝 ÷ 月の回数', 'hibiki-english' ), hibiki_format_yen( $c['per_lesson'] ) ),
	);
	$html = '';
	foreach ( $rows as $row ) {
		$html .= sprintf( '<tr><th scope="row">%1$s<span class="cost__note">%2$s</span></th><td class="num">%3$s</td></tr>', esc_html( $row[0] ), esc_html( $row[1] ), esc_html( $row[2] ) );
	}
	return sprintf(
		'<div %1$s><table><caption class="screen-reader-text">%2$s</caption><tbody>%3$s</tbody><tfoot><tr><th scope="row">%4$s<span class="cost__note">%5$s</span></th><td class="num">%6$s</td></tr></tfoot></table><p class="cost__remark">%7$s</p></div>',
		get_block_wrapper_attributes( array( 'class' => 'cost' ) ),
		esc_html__( '月謝と費用の内訳（税込）', 'hibiki-english' ),
		$html,
		esc_html__( '初月のお支払い', 'hibiki-english' ),
		esc_html__( '入会金 ＋ 月謝', 'hibiki-english' ),
		esc_html( hibiki_format_yen( $c['entry_fee'] + $c['fee'] ) ),
		esc_html__( '金額はすべて税込です。教材費は含みません（コースにより別途かかります）。', 'hibiki-english' )
	);
}

/**
 * 担当講師（コースの詳細）。コースの「担当講師」に選んだ講師を並べる。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function hibiki_render_course_instructors( $attributes, $content, $block ) {
	$post_id = hibiki_current_id( $block, 'course' );
	if ( ! $post_id ) {
		return hibiki_block_placeholder( __( '担当講師', 'hibiki-english' ) );
	}
	$ids = hibiki_get_course( $post_id )['instructors'];
	if ( ! $ids ) {
		return sprintf( '<p %1$s>%2$s</p>', get_block_wrapper_attributes( array( 'class' => 'no-results__text' ) ), esc_html__( '担当講師は決まりしだいお知らせします。', 'hibiki-english' ) );
	}
	$html = '';
	foreach ( $ids as $id ) {
		$html .= '<li>' . hibiki_instructor_card_html( $id, 3 ) . '</li>';
	}
	return sprintf( '<ul %1$s>%2$s</ul>', get_block_wrapper_attributes( array( 'class' => 'instructor-list is-compact' ) ), $html );
}

/**
 * 体験レッスンの申し込みページの URL。
 *
 * @param array $args クエリ（course_id）。
 * @return string URL。固定ページがない場合は空文字列。
 */
function hibiki_trial_url( $args = array() ) {
	$page = get_page_by_path( 'trial' );
	return $page ? add_query_arg( $args, get_permalink( $page ) ) : '';
}

/**
 * 体験レッスンの導線（コースの詳細の右段）。
 *
 * - 受付中・残りわずか: 体験レッスンの申し込みへ案内する
 * - 満席:             キャンセル待ちの案内に切り替える
 * - 開講準備中:       申し込みの導線を出さず、受付中のコースへ案内する
 *
 * 受付中・満席のコースでは、幅の狭い画面で画面下部に固定するボタンも出力する。
 *
 * @param array    $attributes 属性。
 * @param string   $content    内容。
 * @param WP_Block $block      ブロック。
 * @return string HTML。
 */
function hibiki_render_course_cta( $attributes, $content, $block ) {
	$post_id = hibiki_current_id( $block, 'course' );
	if ( ! $post_id ) {
		return hibiki_block_placeholder( __( '体験レッスンの導線', 'hibiki-english' ) );
	}
	$c      = hibiki_get_course( $post_id );
	$school = hibiki_get_school();

	if ( 'preparing' === $c['status'] ) {
		return sprintf(
			'<div %1$s><p class="cta__closed"><strong>%2$s</strong>%3$s</p><a class="cta__secondary" href="%4$s">%5$s</a></div>',
			get_block_wrapper_attributes( array( 'class' => 'cta is-preparing' ) ),
			esc_html__( '開講準備中', 'hibiki-english' ),
			esc_html__( '申し込みの受付は、開講が決まってから始めます。', 'hibiki-english' ),
			esc_url( get_post_type_archive_link( 'course' ) ),
			esc_html__( '受付中のコースを見る', 'hibiki-english' )
		);
	}

	$full = 'full' === $c['status'];
	if ( $full ) {
		$url   = hibiki_trial_url( array( 'course_id' => $post_id ) );
		$label = __( 'キャンセル待ちを申し込む', 'hibiki-english' );
		$short = __( 'キャンセル待ち', 'hibiki-english' );
		$lead  = __( '現在満席です。空きが出た場合に、登録順にご連絡します。', 'hibiki-english' );
	} else {
		$url   = hibiki_trial_url( array( 'course_id' => $post_id ) );
		$label = __( '体験レッスンを申し込む', 'hibiki-english' );
		$short = __( '体験レッスン', 'hibiki-english' );
		$lead  = 'few' === $c['status'] ? __( '残りわずかです。体験レッスン（無料・1回）で雰囲気をお確かめください。', 'hibiki-english' ) : __( '体験レッスンは無料・1回です。レベルの確認もこのときに行います。', 'hibiki-english' );
	}

	return sprintf(
		'<div %1$s><p class="cta__lead">%2$s</p><a class="cta__primary" href="%3$s">%4$s</a><a class="cta__tel" href="%5$s">%6$s<span>%7$s</span><span class="num">%8$s</span></a><p class="cta__note">%9$s</p>'
		. '<div class="cta__fixed" role="group" aria-label="%10$s"><a class="cta__tel" href="%5$s">%6$s<span>%11$s</span></a><a class="cta__primary" href="%3$s">%12$s</a></div></div>',
		get_block_wrapper_attributes( array( 'class' => 'cta' . ( $full ? ' is-full' : '' ) ) ),
		esc_html( $lead ),
		esc_url( $url ),
		esc_html( $label ),
		esc_url( hibiki_tel_url() ),
		hibiki_icon( 'tel' ),
		esc_html__( '電話で相談', 'hibiki-english' ),
		esc_html( $school['tel'] ),
		/* translators: 1: 受付時間 2: 休校日 */
		esc_html( sprintf( __( '受付 %1$s ／ 休校 %2$s', 'hibiki-english' ), $school['hours'], $school['closed'] ) ),
		esc_attr__( '申し込み', 'hibiki-english' ),
		esc_html__( '電話', 'hibiki-english' ),
		esc_html( $full ? $short : __( '体験レッスンを申し込む', 'hibiki-english' ) )
	);
}

/**
 * 対象から選ぶ（キッズ・中高生・大人の入口）。対象ごとに、受付中・満席のコース数を示す。
 *
 * @return string HTML。
 */
function hibiki_render_target_entry() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'target',
			'hide_empty' => false,
			'orderby'    => 'term_id',
		)
	);
	$html  = '';
	foreach ( $terms as $term ) {
		$html .= sprintf(
			'<li><a href="%1$s">%2$s<span class="target-entry__name">%3$s</span><span class="target-entry__desc">%4$s</span><span class="target-entry__count"><span class="num">%5$d</span>%6$s</span></a></li>',
			esc_url( get_term_link( $term ) ),
			hibiki_icon( 'target-' . $term->slug ),
			esc_html( $term->name ),
			esc_html( $term->description ),
			hibiki_count_courses( $term->term_id ),
			esc_html__( 'コース', 'hibiki-english' )
		);
	}
	return sprintf( '<ul %1$s>%2$s</ul>', get_block_wrapper_attributes( array( 'class' => 'target-entry' ) ), $html );
}

/**
 * 教室情報の定義リスト。
 *
 * @return string HTML。
 */
function hibiki_render_school_info() {
	$school = hibiki_get_school();
	$rows   = array( __( '教室', 'hibiki-english' ) => get_bloginfo( 'name' ) );
	foreach ( hibiki_school_labels() as $key => $label ) {
		$rows[ $label ] = $school[ $key ];
	}
	return hibiki_definition_list( $rows, get_block_wrapper_attributes( array( 'class' => 'spec' ) ) );
}

/**
 * 体験レッスンの申し込みフォーム（表示のみ）。
 *
 * コースの詳細から来た場合は、対象のコースを選択した状態とする。満席のコースから来た場合は、
 * キャンセル待ちの登録として案内する。
 *
 * @return string HTML。
 */
function hibiki_render_trial_form() {
	$course   = absint( get_query_var( 'course_id' ) );
	$course   = $course && 'course' === get_post_type( $course ) && 'publish' === get_post_status( $course ) ? $course : 0;
	$waitlist = $course && 'full' === hibiki_get_course( $course )['status'];
	$uid      = wp_unique_id( 'hibiki-trial-' );

	$courses = get_posts(
		array(
			'post_type'      => 'course',
			'posts_per_page' => -1,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'meta_query'     => array( hibiki_listed_clause() ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 開講準備中を除くために必要なため。
		)
	);
	$options = array( '' => __( '相談して決めたい', 'hibiki-english' ) );
	foreach ( $courses as $post ) {
		$c                    = hibiki_get_course( $post->ID );
		$options[ $post->ID ] = get_the_title( $post ) . ( 'full' === $c['status'] ? __( '（満席・キャンセル待ち）', 'hibiki-english' ) : '' );
	}

	$field = static function ( $name, $label, $control, $required = false ) use ( $uid ) {
		return sprintf(
			'<div class="form__field"><label for="%1$s-%2$s">%3$s%4$s</label>%5$s</div>',
			esc_attr( $uid ),
			esc_attr( $name ),
			esc_html( $label ),
			$required ? '<span class="form__req">' . esc_html__( '必須', 'hibiki-english' ) . '</span>' : '',
			$control
		);
	};
	$input = static function ( $name, $type, $autocomplete, $required = false ) use ( $uid ) {
		return sprintf( '<input id="%1$s-%2$s" name="%2$s" type="%3$s" autocomplete="%4$s"%5$s>', esc_attr( $uid ), esc_attr( $name ), esc_attr( $type ), esc_attr( $autocomplete ), $required ? ' required' : '' );
	};

	$html  = $field( 'course', __( '希望するコース', 'hibiki-english' ), sprintf( '<select id="%1$s-course" name="course">%2$s</select>', esc_attr( $uid ), hibiki_options_html( $options, $course ? $course : '' ) ) );
	$html .= $field(
		'learner',
		__( '受講する方', 'hibiki-english' ),
		sprintf(
			'<select id="%1$s-learner" name="learner">%2$s</select>',
			esc_attr( $uid ),
			hibiki_options_html(
				array(
					'self'  => __( 'ご本人', 'hibiki-english' ),
					'child' => __( 'お子さま（保護者の方が申し込む）', 'hibiki-english' ),
				),
				'self'
			)
		)
	);
	$html .= $field( 'name', __( 'お名前（保護者の方が申し込む場合は保護者のお名前）', 'hibiki-english' ), $input( 'name', 'text', 'name', true ), true );
	$html .= $field( 'email', __( 'メールアドレス', 'hibiki-english' ), $input( 'email', 'email', 'email', true ), true );
	$html .= $field( 'tel', __( '電話番号', 'hibiki-english' ), $input( 'tel', 'tel', 'tel' ) );
	$html .= $field( 'date', __( '希望日時（第1希望）', 'hibiki-english' ), $input( 'date', 'text', 'off' ) );
	$html .= $field( 'message', __( 'ご質問・英語の学習歴など', 'hibiki-english' ), sprintf( '<textarea id="%1$s-message" name="message" rows="5"></textarea>', esc_attr( $uid ) ) );

	$notice = __( 'このフォームは制作サンプルのため表示のみです。送信はできません。', 'hibiki-english' );
	$lead   = $waitlist
		? sprintf(
			'<p class="form__waitlist"><strong>%1$s</strong>%2$s</p>',
			/* translators: %s: コース名 */
			esc_html( sprintf( __( '「%s」は現在満席です。', 'hibiki-english' ), get_the_title( $course ) ) ),
			esc_html__( 'このフォームはキャンセル待ちの登録として承ります。空きが出た場合に、登録順にご連絡します。', 'hibiki-english' )
		)
		: '';

	return sprintf(
		'<div %1$s><p class="form__notice" id="%2$s-notice">%3$s</p>%4$s<form class="form" action="#" method="post" aria-describedby="%2$s-notice">%5$s<button type="submit" disabled>%6$s</button></form></div>',
		get_block_wrapper_attributes(),
		esc_attr( $uid ),
		esc_html( $notice ),
		$lead,
		$html,
		esc_html( $waitlist ? __( 'キャンセル待ちを登録する', 'hibiki-english' ) : __( '体験レッスンを申し込む', 'hibiki-english' ) )
	);
}
