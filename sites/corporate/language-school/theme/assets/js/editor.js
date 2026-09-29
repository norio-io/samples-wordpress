/**
 * ひびき英語スクール（架空）— エディター
 *
 * - テーマの動的ブロックを登録する。表示はサーバー側の描画（inc/blocks.php）をそのまま用いる
 * - コースの編集画面に「コース情報」パネル、講師の編集画面に「講師情報」パネルを追加し、入力欄（投稿メタ）を編集できるようにする
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
			icon: 'welcome-learn-more',
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

	const numberField = ( meta, set, key, label, step ) =>
		el( TextControl, {
			key,
			label,
			type: 'number',
			min: 0,
			step,
			value: meta[ key ] ?? '',
			onChange: ( value ) => set( key, value === '' ? 0 : Number( value ) ),
			...common,
		} );

	const textField = ( meta, set, key, label, help ) =>
		el( TextControl, { key, label, help, value: meta[ key ] || '', onChange: ( value ) => set( key, value ), ...common } );

	function InstructorPicker( { value, onChange } ) {
		const instructors = useSelect(
			( select ) =>
				select( 'core' ).getEntityRecords( 'postType', 'instructor', {
					per_page: -1,
					status: 'publish',
					orderby: 'menu_order',
					order: 'asc',
					_fields: 'id,title',
				} ),
			[]
		);
		return el(
			'fieldset',
			{ className: 'hibiki-panel__group' },
			el( 'legend', null, __( '担当講師', 'hibiki-english' ) ),
			instructors === null
				? el( Spinner )
				: instructors.map( ( item ) =>
						el( CheckboxControl, {
							key: item.id,
							label: item.title.rendered || item.title.raw,
							checked: value.includes( item.id ),
							onChange: ( checked ) => onChange( checked ? [ ...value, item.id ] : value.filter( ( id ) => id !== item.id ) ),
							__nextHasNoMarginBottom: true,
						} )
				  )
		);
	}

	function CoursePanel() {
		const postType = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostType(), [] );
		const [ meta, set ] = useMeta();
		if ( postType !== 'course' ) {
			return null;
		}
		const status = meta.status || 'open';
		const notices = {
			full: __( '満席のコースは一覧に残り、「満席」の札が付きます。申し込みの導線はキャンセル待ちの案内に切り替わります。', 'hibiki-english' ),
			preparing: __( '開講準備中のコースは、トップ・コース一覧・対象別の一覧に表示されません。詳細ページには「開講準備中」と表示されます。', 'hibiki-english' ),
		};

		return el(
			PluginDocumentSettingPanel,
			{ name: 'hibiki-course', title: __( 'コース情報', 'hibiki-english' ), className: 'hibiki-panel' },
			stack(
				el( SelectControl, {
					label: __( '募集状況', 'hibiki-english' ),
					value: status,
					options: toOptions( data.status ),
					onChange: ( value ) => set( 'status', value ),
					...common,
				} ),
				notices[ status ] && el( Notice, { status: 'warning', isDismissible: false }, notices[ status ] ),
				numberField( meta, set, 'fee', __( '月謝（円、税込）', 'hibiki-english' ), 100 ),
				numberField( meta, set, 'entry_fee', __( '入会金（円、税込）', 'hibiki-english' ), 100 ),
				numberField( meta, set, 'minutes', __( '1回の時間（分）', 'hibiki-english' ), 5 ),
				numberField( meta, set, 'times', __( '月の回数', 'hibiki-english' ), 1 ),
				el( SelectControl, {
					label: __( '形式', 'hibiki-english' ),
					value: meta.format || 'group',
					options: toOptions( data.format ),
					onChange: ( value ) => set( 'format', value ),
					...common,
				} ),
				numberField( meta, set, 'capacity', __( '定員（名）', 'hibiki-english' ), 1 ),
				textField( meta, set, 'schedule', __( '開講曜日と時間帯', 'hibiki-english' ), __( '例: 火曜日と木曜日 19:00–19:50', 'hibiki-english' ) ),
				el( SelectControl, {
					label: __( 'レベル', 'hibiki-english' ),
					value: meta.level || 'starter',
					options: toOptions( data.level ),
					onChange: ( value ) => set( 'level', value ),
					...common,
				} ),
				el( TextareaControl, {
					label: __( '1回のレッスンの流れ', 'hibiki-english' ),
					help: __( '1行に1つの手順を書きます。末尾に「（10分）」のように時間を書くと、時間を右に揃えて表示します。', 'hibiki-english' ),
					rows: 5,
					value: meta.flow || '',
					onChange: ( value ) => set( 'flow', value ),
					__nextHasNoMarginBottom: true,
				} ),
				el( InstructorPicker, {
					value: ( Array.isArray( meta.instructors ) ? meta.instructors : [] ).map( Number ),
					onChange: ( value ) => set( 'instructors', value ),
				} )
			)
		);
	}

	function InstructorPanel() {
		const postType = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostType(), [] );
		const [ meta, set ] = useMeta();
		if ( postType !== 'instructor' ) {
			return null;
		}
		return el(
			PluginDocumentSettingPanel,
			{ name: 'hibiki-instructor', title: __( '講師情報', 'hibiki-english' ), className: 'hibiki-panel' },
			stack(
				textField( meta, set, 'language', __( '担当言語', 'hibiki-english' ) ),
				numberField( meta, set, 'years', __( '指導歴（年）', 'hibiki-english' ), 1 ),
				textField( meta, set, 'specialty', __( '得意分野', 'hibiki-english' ) ),
				textField( meta, set, 'qualifications', __( '保有資格', 'hibiki-english' ) ),
				el( SelectControl, {
					label: __( 'イラスト', 'hibiki-english' ),
					help: __( 'アイキャッチ画像を設定した場合は、そちらを表示します。', 'hibiki-english' ),
					value: meta.portrait || '',
					options: toOptions( data.portrait ),
					onChange: ( value ) => set( 'portrait', value ),
					...common,
				} ),
				el( 'p', { className: 'hibiki-panel__note' }, __( '担当コースは、各コースの「担当講師」から自動で表示します。', 'hibiki-english' ) )
			)
		);
	}

	wp.plugins.registerPlugin( 'hibiki-course-panel', { render: CoursePanel } );
	wp.plugins.registerPlugin( 'hibiki-instructor-panel', { render: InstructorPanel } );

	// 入力パネルは初回だけ開いた状態にする。更新担当者が入力欄を探さずに済むようにするため。
	// 2回目以降は、閉じた・開いたの選択を尊重する。
	wp.domReady( () => {
		const prefs = wp.data.select( 'core/preferences' );
		if ( prefs.get( 'hibiki-english', 'panelInitialized' ) ) {
			return;
		}
		[ 'hibiki-course-panel/hibiki-course', 'hibiki-instructor-panel/hibiki-instructor' ].forEach( ( panel ) => {
			if ( ! wp.data.select( 'core/editor' ).isEditorPanelOpened( panel ) ) {
				wp.data.dispatch( 'core/editor' ).toggleEditorPanelOpened( panel );
			}
		} );
		wp.data.dispatch( 'core/preferences' ).set( 'hibiki-english', 'panelInitialized', true );
	} );
} )( window.wp, window.hibikiEditor );
