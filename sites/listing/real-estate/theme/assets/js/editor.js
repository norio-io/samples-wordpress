/**
 * ひなた不動産（架空）— エディター
 *
 * - テーマの動的ブロックを登録する。表示はサーバー側の描画（inc/blocks.php）をそのまま用いる
 * - 物件の編集画面に「物件情報」パネルを追加し、入力欄（投稿メタ）を編集できるようにする
 *
 * ビルド工程を持たないため、JSX を使わず wp.element.createElement で記述する。
 */
( function ( wp, data ) {
	const el = wp.element.createElement;
	const { __ } = wp.i18n;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, SelectControl, TextControl, CheckboxControl, Notice } = wp.components;
	const { useSelect, useDispatch } = wp.data;
	const ServerSideRender = wp.serverSideRender;

	// ------------------------------------------------------------ ブロック

	Object.entries( data.blocks ).forEach( ( [ name, def ] ) => {
		wp.blocks.registerBlockType( name, {
			apiVersion: 3,
			title: def.title,
			category: 'theme',
			icon: 'building',
			attributes: def.attributes,
			supports: { html: false },
			edit( props ) {
				const blockProps = useBlockProps();
				const postId = useSelect( ( select ) => select( 'core/editor' )?.getCurrentPostId?.(), [] );
				const inspector =
					name === 'hinata/search'
						? el(
								InspectorControls,
								null,
								el(
									PanelBody,
									{ title: __( '表示', 'hinata-realty' ) },
									el( SelectControl, {
										label: __( '種類', 'hinata-realty' ),
										value: props.attributes.variant,
										options: [
											{ label: __( '検索パネル（トップ）', 'hinata-realty' ), value: 'panel' },
											{ label: __( '絞り込み（物件一覧）', 'hinata-realty' ), value: 'sidebar' },
										],
										onChange: ( variant ) => props.setAttributes( { variant } ),
									} )
								)
						  )
						: null;
				return el(
					'div',
					blockProps,
					inspector,
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

	// ------------------------------------------------------------ 物件情報パネル

	const NUMBER_FIELDS = [
		[ 'rent', __( '賃料（円）', 'hinata-realty' ), 1000 ],
		[ 'fee', __( '管理費・共益費（円）', 'hinata-realty' ), 500 ],
		[ 'deposit', __( '敷金（か月）', 'hinata-realty' ), 0.5 ],
		[ 'key_money', __( '礼金（か月）', 'hinata-realty' ), 0.5 ],
		[ 'sqm', __( '専有面積（㎡）', 'hinata-realty' ), 0.1 ],
		[ 'floor', __( '所在階', 'hinata-realty' ), 1 ],
		[ 'floors', __( '建物の階数', 'hinata-realty' ), 1 ],
		[ 'walk', __( '駅徒歩（分）', 'hinata-realty' ), 1 ],
	];
	const TEXT_FIELDS = [
		[ 'station', __( '最寄り駅', 'hinata-realty' ) ],
		[ 'address', __( '所在地', 'hinata-realty' ) ],
	];

	function PropertyPanel() {
		const postType = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostType(), [] );
		const meta = useSelect( ( select ) => select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {}, [] );
		const { editPost } = useDispatch( 'core/editor' );
		if ( postType !== 'property' ) {
			return null;
		}
		const set = ( key, value ) => editPost( { meta: { [ key ]: value } } );
		const equipment = Array.isArray( meta.equipment ) ? meta.equipment : [];
		const PluginDocumentSettingPanel = ( wp.editor && wp.editor.PluginDocumentSettingPanel ) || wp.editPost.PluginDocumentSettingPanel;

		return el(
			PluginDocumentSettingPanel,
			{ name: 'hinata-property', title: __( '物件情報', 'hinata-realty' ), className: 'hinata-property-panel' },
			el( SelectControl, {
				label: __( '掲載状態', 'hinata-realty' ),
				help: __( '「成約済み」にすると、公開側の一覧・検索結果・トップから外れます。', 'hinata-realty' ),
				value: meta.status || 'available',
				options: Object.entries( data.status ).map( ( [ value, label ] ) => ( { value, label } ) ),
				onChange: ( value ) => set( 'status', value ),
			} ),
			meta.status === 'contracted' &&
				el( Notice, { status: 'warning', isDismissible: false }, __( '成約済みの物件は、詳細ページに「成約済み」と表示し、問い合わせ導線を出しません。', 'hinata-realty' ) ),
			NUMBER_FIELDS.map( ( [ key, label, step ] ) =>
				el( TextControl, {
					key,
					label,
					type: 'number',
					min: 0,
					step,
					value: meta[ key ] ?? '',
					onChange: ( value ) => set( key, value === '' ? 0 : Number( value ) ),
					__next40pxDefaultSize: true,
					__nextHasNoMarginBottom: true,
				} )
			),
			el( TextControl, {
				label: __( '築年月', 'hinata-realty' ),
				type: 'month',
				value: meta.built || '',
				onChange: ( value ) => set( 'built', value ),
				__next40pxDefaultSize: true,
				__nextHasNoMarginBottom: true,
			} ),
			TEXT_FIELDS.map( ( [ key, label ] ) =>
				el( TextControl, {
					key,
					label,
					value: meta[ key ] || '',
					onChange: ( value ) => set( key, value ),
					__next40pxDefaultSize: true,
					__nextHasNoMarginBottom: true,
				} )
			),
			el(
				'fieldset',
				{ className: 'hinata-property-panel__equipment' },
				el( 'legend', null, __( '設備', 'hinata-realty' ) ),
				Object.entries( data.equipment ).map( ( [ key, label ] ) =>
					el( CheckboxControl, {
						key,
						label,
						checked: equipment.includes( key ),
						onChange: ( checked ) =>
							set(
								'equipment',
								checked ? [ ...equipment, key ] : equipment.filter( ( item ) => item !== key )
							),
						__nextHasNoMarginBottom: true,
					} )
				)
			)
		);
	}

	wp.plugins.registerPlugin( 'hinata-property-panel', { render: PropertyPanel } );

	// 「物件情報」パネルは初回だけ開いた状態にする。更新担当者が入力欄を探さずに済むようにするため。
	// 2回目以降は、閉じた・開いたの選択を尊重する。
	wp.domReady( () => {
		const prefs = wp.data.select( 'core/preferences' );
		if ( prefs.get( 'hinata-realty', 'panelInitialized' ) ) {
			return;
		}
		const panel = 'hinata-property-panel/hinata-property';
		if ( ! wp.data.select( 'core/editor' ).isEditorPanelOpened( panel ) ) {
			wp.data.dispatch( 'core/editor' ).toggleEditorPanelOpened( panel );
		}
		wp.data.dispatch( 'core/preferences' ).set( 'hinata-realty', 'panelInitialized', true );
	} );
} )( window.wp, window.hinataEditor );
