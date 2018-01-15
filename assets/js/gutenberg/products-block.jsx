const { __ } = wp.i18n;
const { registerBlockType, InspectorControls, BlockControls } = wp.blocks;
const { Toolbar } = wp.components;
const { RangeControl, ToggleControl, SelectControl } = InspectorControls;

/**
 * Products block.
 */
registerBlockType( 'woocommerce/products', {
	title: __( 'Products' ),
	icon: 'universal-access-alt', // @todo Needs a good icon.
	category: 'widgets',

	attributes: {
		layout: {
			type: 'string',
			default: 'grid',
		},
		columns: {
			type: 'number',
			default: 3,
		},
		rows: {
			type: 'number',
			default: 1,
		},
		display_title: {
			type: 'boolean',
			default: true,
		},
		display_price: {
			type: 'boolean',
			default: true,
		},
		display_add_to_cart: {
			type: 'boolean',
			default: false,
		},
		order: {
			type: 'string',
			default: 'newness',
		}

		// @todo
		// Needs attributes for the product/category select menu:
		// display: "specific_products", "product_category", etc.
		// display_setting: array of product/category ids?
	},

	// @todo This will need to be converted to pull dynamic data from the API similar to https://wordpress.org/gutenberg/handbook/blocks/creating-dynamic-blocks/.
	edit( props ) {
		const { attributes, className, focus, setAttributes, setFocus } = props;
		const { layout, rows, columns, display_title, display_price, display_add_to_cart, order } = attributes;

		/**
		 * Get the components for the sidebar settings area that is rendered while focused on a Products block.
		 */
		function getInspectorControls() {
			return (
				<InspectorControls key="inspector">
					<h3>{ __( 'Layout' ) }</h3>
					<RangeControl
						label={ __( 'Columns' ) }
						value={ columns }
						onChange={ ( value ) => setAttributes( { columns: value } ) }
						min={ 1 }
						max={ 6 }
					/>
					<RangeControl
						label={ __( 'Rows' ) }
						value={ rows }
						onChange={ ( value ) => setAttributes( { rows: value } ) }
						min={ 1 }
						max={ 6 }
					/>
					<ToggleControl
						label={ __( 'Display title' ) }
						checked={ display_title }
						onChange={ () => setAttributes( { display_title: ! display_title } ) }
					/>
					<ToggleControl
						label={ __( 'Display price' ) }
						checked={ display_price }
						onChange={ () => setAttributes( { display_price: ! display_price } ) }
					/>
					<ToggleControl
						label={ __( 'Display add to cart button' ) }
						checked={ display_add_to_cart }
						onChange={ () => setAttributes( { display_add_to_cart: ! display_add_to_cart } ) }
					/>
					<SelectControl
						key="query-panel-select"
						label={ __( 'Order' ) }
						value={ order }
						options={ [
							{
								label: __( 'Newness' ),
								value: 'newness',
							},
							{
								label: __( 'Best Selling' ),
								value: 'sales',
							},
							{
								label: __( 'Title' ),
								value: 'title',
							},
						] }
						onChange={ ( value ) => setAttributes( { order: value } ) }
					/>
				</InspectorControls>
			);
		};

		function getToolbarControls() {
			const layoutControls = [
				{
					icon: 'list-view',
					title: __( 'List View' ),
					onClick: () => setAttributes( { layout: 'list' } ),
					isActive: layout === 'list',
				},
				{
					icon: 'grid-view',
					title: __( 'Grid View' ),
					onClick: () => setAttributes( { layout: 'grid' } ),
					isActive: layout === 'grid',
				},
			];

			// @todo Hook this up to the Edit modal thing.
			const productSourceControls = [
				{
					icon: 'edit',
					title: __( 'Edit' ),
					onClick: function(){}
				},

			];

			return (
				<BlockControls key="controls">
					<Toolbar controls={ layoutControls } />
					<Toolbar controls={ productSourceControls } />
				</BlockControls>
			);
		}

		return [
			( !! focus ) ? getInspectorControls() : null,
			( !! focus ) ? getToolbarControls() : null,
			<div className={ className }>This needs to do stuff.</div>
		];
	},

	save() {
		// @todo
		// This can either build a shortcode out of all the settings (good backwards compatibility but may need extra shortcode work)
		// Or it can return null and the html can be generated with PHP like the example at https://wordpress.org/gutenberg/handbook/blocks/creating-dynamic-blocks/
		return '[products limit="3"]';
	},
} );
