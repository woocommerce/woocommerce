/* global wp, woocommerce_admin_product_block_editor */
/**
 * Product edit screen integration for the block editor.
 *
 * Catalog visibility, featured and gallery values are written to hidden inputs in the
 * Product data meta box, so they are saved together with the other meta box fields.
 */
( function ( wp, settings ) {
	const el = wp.element.createElement;
	const { Fragment, useState } = wp.element;
	const { __ } = wp.i18n;
	const { Button, Dropdown, Flex, FormToggle, RadioControl, Spinner } =
		wp.components;
	const { MediaUpload, MediaUploadCheck } = wp.blockEditor;
	const { useSelect } = wp.data;

	const getField = ( name ) =>
		document.querySelector(
			'#woocommerce-product-block-editor-fields [name="' + name + '"]'
		);

	const PanelRow = ( { label, children } ) =>
		el(
			Flex,
			{ className: 'editor-post-panel__row' },
			el( 'div', { className: 'editor-post-panel__row-label' }, label ),
			el( 'div', { className: 'editor-post-panel__row-control' }, children )
		);

	const CatalogVisibilityRow = () => {
		const field = getField( '_visibility' );
		const [ value, setValue ] = useState( field ? field.value : '' );

		if ( ! field ) {
			return null;
		}

		const onChange = ( nextValue ) => {
			field.value = nextValue;
			setValue( nextValue );
		};

		return el(
			PanelRow,
			{ label: __( 'Catalog', 'woocommerce' ) },
			el( Dropdown, {
				popoverProps: {
					placement: 'left-start',
					offset: 36,
					shift: true,
				},
				focusOnMount: true,
				renderToggle: ( { isOpen, onToggle } ) =>
					el(
						Button,
						{
							size: 'compact',
							variant: 'tertiary',
							'aria-expanded': isOpen,
							onClick: onToggle,
						},
						settings.visibility_options[ value ] || value
					),
				renderContent: () =>
					el(
						'div',
						{ style: { padding: '16px', minWidth: '248px' } },
						el( RadioControl, {
							label: __( 'Catalog visibility', 'woocommerce' ),
							help: __(
								'This setting determines which shop pages products will be listed on.',
								'woocommerce'
							),
							selected: value,
							options: Object.keys(
								settings.visibility_options
							).map( ( key ) => ( {
								value: key,
								label: settings.visibility_options[ key ],
							} ) ),
							onChange,
						} )
					),
			} )
		);
	};

	const FeaturedRow = () => {
		const field = getField( '_featured' );
		const [ isFeatured, setIsFeatured ] = useState(
			field ? ! field.disabled : false
		);

		if ( ! field ) {
			return null;
		}

		return el(
			PanelRow,
			{ label: __( 'Featured', 'woocommerce' ) },
			// Padded to line up with the button values in the other rows.
			el(
				'span',
				{ style: { display: 'flex', paddingInlineStart: '12px' } },
				el( FormToggle, {
					checked: isFeatured,
					'aria-label': __( 'This is a featured product', 'woocommerce' ),
					onChange: () => {
						// Disabled inputs are not submitted, which saves the product as not featured.
						field.disabled = isFeatured;
						setIsFeatured( ! isFeatured );
					},
				} )
			)
		);
	};

	const ProductGallery = () => {
		const field = getField( 'product_image_gallery' );
		const [ ids, setIds ] = useState( () =>
			field ? field.value.split( ',' ).filter( Boolean ).map( Number ) : []
		);
		const images = useSelect(
			( select ) => ids.map( ( id ) => select( 'core' ).getMedia( id ) ),
			[ ids ]
		);

		if ( ! field ) {
			return null;
		}

		const update = ( nextIds ) => {
			field.value = nextIds.join( ',' );
			setIds( nextIds );
		};

		return el(
			'div',
			{ className: 'woocommerce-product-block-editor-gallery' },
			ids.length > 0 &&
				el(
					'ul',
					{
						style: {
							display: 'grid',
							gridTemplateColumns: 'repeat(4, 1fr)',
							gap: '8px',
							margin: '0 0 8px',
						},
					},
					ids.map( ( id, index ) => {
						const image = images[ index ];
						const src =
							image &&
							( image.media_details?.sizes?.thumbnail?.source_url ||
								image.source_url );

						return el(
							'li',
							{
								key: id,
								style: {
									position: 'relative',
									margin: 0,
									aspectRatio: '1',
									background: '#f0f0f0',
									borderRadius: '2px',
									overflow: 'hidden',
								},
							},
							src
								? el( 'img', {
										src,
										alt: image.alt_text || '',
										style: {
											width: '100%',
											height: '100%',
											objectFit: 'cover',
											display: 'block',
										},
								  } )
								: el( Spinner ),
							el( Button, {
								icon: 'no-alt',
								size: 'small',
								label: __( 'Remove image', 'woocommerce' ),
								onClick: () =>
									update(
										ids.filter(
											( galleryId ) => galleryId !== id
										)
									),
								style: {
									position: 'absolute',
									top: 0,
									insetInlineEnd: 0,
									background: 'rgba(255,255,255,0.9)',
									minWidth: 0,
									padding: 0,
								},
							} )
						);
					} )
				),
			el(
				MediaUploadCheck,
				null,
				el( MediaUpload, {
					title: __( 'Product gallery', 'woocommerce' ),
					multiple: true,
					gallery: true,
					addToGallery: ids.length > 0,
					value: ids,
					allowedTypes: [ 'image' ],
					onSelect: ( items ) =>
						update( items.map( ( item ) => item.id ) ),
					render: ( { open } ) =>
						el(
							Button,
							{
								__next40pxDefaultSize: true,
								className: 'editor-post-featured-image__toggle',
								onClick: open,
							},
							ids.length > 0
								? __( 'Edit product gallery', 'woocommerce' )
								: __( 'Add product gallery images', 'woocommerce' )
						),
				} )
			)
		);
	};

	// Gallery right below the product image.
	wp.hooks.addFilter(
		'editor.PostFeaturedImage',
		'woocommerce/product-gallery',
		( PostFeaturedImage ) => ( props ) =>
			el(
				Fragment,
				null,
				el( PostFeaturedImage, props ),
				el( ProductGallery )
			)
	);

	// Catalog visibility and featured next to Status, Slug, Template.
	wp.plugins.registerPlugin( 'woocommerce-product-catalog-visibility', {
		render: () =>
			el(
				wp.editor.PluginPostStatusInfo,
				{ className: 'woocommerce-product-catalog-visibility' },
				el(
					'div',
					{ style: { width: '100%' } },
					el( CatalogVisibilityRow ),
					el( FeaturedRow )
				)
			),
	} );

	wp.domReady( () => {
		// The Short description meta box saves the excerpt after the post itself, so a value typed
		// in the editor's own Excerpt panel would be overwritten. Keep only the meta box.
		wp.data.dispatch( 'core/editor' ).removeEditorPanel( 'post-excerpt' );

		// The meta boxes load closed (see WC_Admin_Post_Types::close_block_editor_meta_boxes()), so keep
		// the pane open at its automatic height: it then shows just their headers below the description.
		wp.data
			.dispatch( 'core/preferences' )
			.set( 'core/edit-post', 'metaBoxesMainIsOpen', true );
		wp.data
			.dispatch( 'core/preferences' )
			.set( 'core/edit-post', 'metaBoxesMainOpenHeight', undefined );
	} );
} )( window.wp, woocommerce_admin_product_block_editor );
