/**
 * 社会福祉法人もえぎ会（架空）採用サイト — エディター
 *
 * - テーマの動的ブロックを登録する。表示はサーバー側の描画（inc/blocks.php）をそのまま用いる
 * - 求人・施設・職員の声の編集画面に入力パネル（求人情報・施設情報・職員の情報）を追加し、入力欄（投稿メタ）を編集できるようにする
 *
 * ビルド工程を持たないため、JSX を使わず wp.element.createElement で記述する。
 */
( function ( wp, data ) {
	const el = wp.element.createElement;
	const { __ } = wp.i18n;
	const { useBlockProps } = wp.blockEditor;
	const { SelectControl, TextControl, TextareaControl, CheckboxControl, Notice, Spinner } = wp.components;
	const { useSelect, useDispatch } = wp.data;
	const ServerSideRender = wp.serverSideRender;

	const toOptions = ( map ) => Object.entries( map ).map( ( [ value, label ] ) => ( { value, label } ) );
	const common = { __next40pxDefaultSize: true, __nextHasNoMarginBottom: true };
	// 入力欄の下の余白を無くしているため、欄どうしの間隔はパネル側で揃える。
	const stack = ( ...children ) => el( 'div', { style: { display: 'grid', gap: '16px' } }, ...children );

	// ------------------------------------------------------------ ブロック

	Object.entries( data.blocks ).forEach( ( [ name, def ] ) => {
		wp.blocks.registerBlockType( name, {
			apiVersion: 3,
			title: def.title,
			category: 'theme',
			icon: 'id-alt',
			attributes: def.attributes,
			usesContext: def.usesContext,
			supports: { html: false },
			edit( props ) {
				const blockProps = useBlockProps();
				const currentId = useSelect( ( select ) => select( 'core/editor' )?.getCurrentPostId?.(), [] );
				// クエリループの中では、各項目の投稿を描画の対象とする。
				const postId = props.context?.postId || currentId;
				return el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: name,
						attributes: props.attributes,
						urlQueryArgs: postId ? { post_id: postId } : {},
					} )
				);
			},
			save: () => null,
		} );
	} );

	// ------------------------------------------------------------ 入力パネル

	const PluginDocumentSettingPanel = ( wp.editor && wp.editor.PluginDocumentSettingPanel ) || wp.editPost.PluginDocumentSettingPanel;

	function useMeta() {
		const meta = useSelect( ( select ) => select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {}, [] );
		const { editPost } = useDispatch( 'core/editor' );
		return [ meta, ( key, value ) => editPost( { meta: { [ key ]: value } } ) ];
	}

	function usePostType() {
		return useSelect( ( select ) => select( 'core/editor' ).getCurrentPostType(), [] );
	}

	const numberField = ( meta, set, key, label, step, help ) =>
		el( TextControl, {
			key,
			label,
			help,
			type: 'number',
			min: 0,
			step,
			value: meta[ key ] ?? '',
			onChange: ( value ) => set( key, value === '' ? 0 : Number( value ) ),
			...common,
		} );

	const textField = ( meta, set, key, label, help ) =>
		el( TextControl, { key, label, help, value: meta[ key ] || '', onChange: ( value ) => set( key, value ), ...common } );

	const selectField = ( meta, set, key, label, options, fallback, help ) =>
		el( SelectControl, {
			key,
			label,
			help,
			value: meta[ key ] || fallback,
			options: toOptions( options ),
			onChange: ( value ) => set( key, value ),
			...common,
		} );

	// 勤務施設は、公開中の施設から選ぶ。値は施設の投稿 ID とする。
	function FacilityPicker( { value, onChange, noneLabel } ) {
		const facilities = useSelect(
			( select ) =>
				select( 'core' ).getEntityRecords( 'postType', 'facility', {
					per_page: -1,
					status: 'publish',
					orderby: 'menu_order',
					order: 'asc',
					_fields: 'id,title',
				} ),
			[]
		);
		if ( facilities === null ) {
			return el( Spinner );
		}
		return el( SelectControl, {
			label: __( '勤務施設', 'moegi-recruit' ),
			value: String( value || 0 ),
			options: [
				{ value: '0', label: noneLabel },
				...facilities.map( ( item ) => ( { value: String( item.id ), label: item.title.rendered || item.title.raw } ) ),
			],
			onChange: ( id ) => onChange( Number( id ) ),
			...common,
		} );
	}

	function JobPanel() {
		const [ meta, set ] = useMeta();
		if ( usePostType() !== 'job' ) {
			return null;
		}
		const status = meta.status || 'open';
		const expired = !! meta.deadline && meta.deadline < data.today;
		let notice = null;
		if ( expired ) {
			notice = __( '掲載期限を過ぎています。募集停止と同じ扱いとなり、一覧から外れ、構造化データも出力しません。掲載を続ける場合は期限を延ばしてください。', 'moegi-recruit' );
		} else if ( status === 'closed' ) {
			notice = __( '募集停止の求人は、トップ・求人一覧・職種別の一覧・施設の詳細に表示されません。詳細ページには募集を停止している旨を表示します。', 'moegi-recruit' );
		} else if ( status === 'urgent' ) {
			notice = __( '急募の求人には「急募」の札が付き、トップと一覧で他の求人より前に並びます。', 'moegi-recruit' );
		}

		return el(
			PluginDocumentSettingPanel,
			{ name: 'moegi-job', title: __( '求人情報', 'moegi-recruit' ), className: 'moegi-panel' },
			stack(
				selectField( meta, set, 'status', __( '募集状況', 'moegi-recruit' ), data.status, 'open' ),
				el( TextControl, {
					label: __( '掲載期限', 'moegi-recruit' ),
					help: __( 'この日を過ぎると、自動で募集停止として扱います。空欄の場合は期限なしとします。', 'moegi-recruit' ),
					type: 'date',
					value: meta.deadline || '',
					onChange: ( value ) => set( 'deadline', value ),
					...common,
				} ),
				notice && el( Notice, { status: expired || status === 'closed' ? 'warning' : 'info', isDismissible: false }, notice ),
				el( FacilityPicker, { value: meta.facility, noneLabel: __( '未定（面接時に相談）', 'moegi-recruit' ), onChange: ( value ) => set( 'facility', value ) } ),
				selectField( meta, set, 'wage_unit', __( '給与の単位', 'moegi-recruit' ), data.wageUnit, 'monthly' ),
				numberField( meta, set, 'wage_min', __( '給与の下限（円）', 'moegi-recruit' ), 10 ),
				numberField( meta, set, 'wage_max', __( '給与の上限（円）', 'moegi-recruit' ), 10, __( '下限と同じ額、または 0 の場合は、下限の額のみを表示します。', 'moegi-recruit' ) ),
				textField( meta, set, 'bonus', __( '賞与', 'moegi-recruit' ), __( '例: 年2回、昨年度実績 3.2か月分', 'moegi-recruit' ) ),
				el( TextareaControl, {
					label: __( '手当の内訳', 'moegi-recruit' ),
					help: __( '1行に1つ、「手当の名前 金額」の形で書きます。例: 夜勤手当 1回 6,000円', 'moegi-recruit' ),
					rows: 4,
					value: meta.allowances || '',
					onChange: ( value ) => set( 'allowances', value ),
					__nextHasNoMarginBottom: true,
				} ),
				textField( meta, set, 'hours', __( '勤務時間', 'moegi-recruit' ), __( '例: 7:00 から 16:00 までを含むシフト制', 'moegi-recruit' ) ),
				numberField( meta, set, 'night_shifts', __( '夜勤の回数（月）', 'moegi-recruit' ), 1, __( '夜勤がない場合は 0 とします。', 'moegi-recruit' ) ),
				numberField( meta, set, 'holidays', __( '年間休日（日）', 'moegi-recruit' ), 1, __( 'パートなど日数を定めない場合は 0 とします（「勤務日数による」と表示）。', 'moegi-recruit' ) ),
				textField( meta, set, 'holiday_note', __( '休日・休暇', 'moegi-recruit' ), __( '例: 4週8休のシフト制、有給休暇、夏季・年末年始休暇', 'moegi-recruit' ) ),
				selectField( meta, set, 'qualification', __( '必要な資格', 'moegi-recruit' ), data.qualification, 'none' ),
				textField( meta, set, 'qualification_note', __( '資格の補足', 'moegi-recruit' ), __( '「その他」の場合は資格名を書きます。例: 普通自動車免許（AT限定可）', 'moegi-recruit' ) ),
				el( CheckboxControl, {
					label: __( '未経験の応募を受け付ける', 'moegi-recruit' ),
					checked: !! meta.inexperienced,
					onChange: ( checked ) => set( 'inexperienced', checked ),
					__nextHasNoMarginBottom: true,
				} ),
				el( 'p', { className: 'moegi-panel__note' }, __( '職種と雇用形態は、右の「職種」「雇用形態」で選びます。仕事内容は本文に書きます。', 'moegi-recruit' ) )
			)
		);
	}

	function FacilityPanel() {
		const [ meta, set ] = useMeta();
		if ( usePostType() !== 'facility' ) {
			return null;
		}
		return el(
			PluginDocumentSettingPanel,
			{ name: 'moegi-facility', title: __( '施設情報', 'moegi-recruit' ), className: 'moegi-panel' },
			stack(
				textField( meta, set, 'kind', __( '施設種別', 'moegi-recruit' ), __( '例: 特別養護老人ホーム', 'moegi-recruit' ) ),
				numberField( meta, set, 'capacity', __( '定員（名）', 'moegi-recruit' ), 1 ),
				textField( meta, set, 'postal_code', __( '郵便番号', 'moegi-recruit' ) ),
				textField( meta, set, 'region', __( '都道府県', 'moegi-recruit' ) ),
				textField( meta, set, 'locality', __( '市区町村', 'moegi-recruit' ) ),
				textField( meta, set, 'street', __( '町名・番地', 'moegi-recruit' ) ),
				textField( meta, set, 'access', __( '最寄り駅からの所要時間', 'moegi-recruit' ), __( '例: 野々瀬駅から徒歩6分', 'moegi-recruit' ) ),
				numberField( meta, set, 'opened', __( '開設年', 'moegi-recruit' ), 1 ),
				numberField( meta, set, 'staff', __( '職員数', 'moegi-recruit' ), 1 ),
				selectField( meta, set, 'art', __( 'イラスト', 'moegi-recruit' ), data.art, '' ),
				el( 'p', { className: 'moegi-panel__note' }, __( '施設の詳細の「募集中の求人」は、各求人の「勤務施設」から自動で表示します。', 'moegi-recruit' ) )
			)
		);
	}

	function VoicePanel() {
		const [ meta, set ] = useMeta();
		if ( usePostType() !== 'voice' ) {
			return null;
		}
		return el(
			PluginDocumentSettingPanel,
			{ name: 'moegi-voice', title: __( '職員の情報', 'moegi-recruit' ), className: 'moegi-panel' },
			stack(
				el( FacilityPicker, { value: meta.facility, noneLabel: __( '設定しない', 'moegi-recruit' ), onChange: ( value ) => set( 'facility', value ) } ),
				numberField( meta, set, 'joined', __( '入職年', 'moegi-recruit' ), 1 ),
				textField( meta, set, 'career', __( '入職前の経歴', 'moegi-recruit' ) ),
				selectField( meta, set, 'portrait', __( 'イラスト', 'moegi-recruit' ), data.portrait, '' ),
				el( TextareaControl, {
					label: __( '1日の流れ', 'moegi-recruit' ),
					help: __( '1行に1つ、「7:00 出勤、申し送り」のように時刻と内容を書きます。', 'moegi-recruit' ),
					rows: 8,
					value: meta.day || '',
					onChange: ( value ) => set( 'day', value ),
					__nextHasNoMarginBottom: true,
				} ),
				el( 'p', { className: 'moegi-panel__note' }, __( 'ひとことは「抜粋」に書きます。求人の詳細には、同じ職種の職員の声を自動で表示します。', 'moegi-recruit' ) )
			)
		);
	}

	wp.plugins.registerPlugin( 'moegi-job-panel', { render: JobPanel } );
	wp.plugins.registerPlugin( 'moegi-facility-panel', { render: FacilityPanel } );
	wp.plugins.registerPlugin( 'moegi-voice-panel', { render: VoicePanel } );

	// 入力パネルは初回だけ開いた状態にする。採用担当者が入力欄を探さずに済むようにするため。
	// 2回目以降は、閉じた・開いたの選択を尊重する。
	wp.domReady( () => {
		const prefs = wp.data.select( 'core/preferences' );
		if ( prefs.get( 'moegi-recruit', 'panelInitialized' ) ) {
			return;
		}
		[ 'moegi-job-panel/moegi-job', 'moegi-facility-panel/moegi-facility', 'moegi-voice-panel/moegi-voice' ].forEach( ( panel ) => {
			if ( ! wp.data.select( 'core/editor' ).isEditorPanelOpened( panel ) ) {
				wp.data.dispatch( 'core/editor' ).toggleEditorPanelOpened( panel );
			}
		} );
		wp.data.dispatch( 'core/preferences' ).set( 'moegi-recruit', 'panelInitialized', true );
	} );
} )( window.wp, window.moegiEditor );
