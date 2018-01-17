const { __ } = wp.i18n;
const { registerBlockType, InspectorControls, BlockControls } = wp.blocks;
const { Toolbar } = wp.components;
const { RangeControl, ToggleControl, SelectControl } = InspectorControls;

/**
 * When the display mode is 'Specific products' search for and add products to the block.
 *
 * @todo Add the functionality and everything.
 */
class ProductsSpecificSelect extends React.Component {
	render() {
		return (
			<div>TODO: Select specific products here</div>
		);
	}
}

/**
 * When the display mode is 'Product category' search for and select product categories to pull products from.
 *
 * @todo Add the functionality and everything.
 */
class ProductsCategorySelect extends React.Component {
	render() {
		return (
			<div>TODO: Select specific product categories here</div>
		);
	}
}

/**
 * The products block when in Edit mode.
 */
class ProductsBlockSettingsEditor extends React.Component {

	/**
	 * Constructor.
	 */
	constructor( props ) {
		super( props );
		this.state = {
			display: props.selected_display
		}

		this.updateDisplay = this.updateDisplay.bind( this );
	}

	/**
	 * Update the display settings for the block.
	 *
	 * @param Event object evt
	 */
	updateDisplay( evt ) {
		this.setState( {
			display: evt.target.value
		} );

		this.props.update_display_callback( evt.target.value );
	}

	/**
	 * Render the display settings dropdown and any extra contextual settings.
	 */
	render() {
		let extra_settings = null;
		if ( 'specific' === this.state.display ) {
			extra_settings = <ProductsSpecificSelect />;
		} else if ( 'category' === this.state.display ) {
			extra_settings = <ProductsCategorySelect />;
		}

		return (
			<div className="wc-product-display-settings">

				<h3>{ __( 'Products' ) }</h3>

				<div className="display-select">
					{ __( 'Display:' ) }
					<select value={ this.state.display } onChange={ this.updateDisplay }>
						<option value="all">{ __( 'All' ) }</option>
						<option value="specific">{ __( 'Specific products' ) }</option>
						<option value="category">{ __( 'Product Category' ) }</option>
					</select>
				</div>

				{ extra_settings }

				<div className="block-footer">
					<button type="button" onClick={ this.props.done_callback }>{ __( 'Done' ) }</button>
				</div>
			</div>
		);
	}
}

/**
 * The products block when in Preview mode.
 *
 * @todo This will need to be converted to pull dynamic data from the API for the preview similar to https://wordpress.org/gutenberg/handbook/blocks/creating-dynamic-blocks/.
 */
class ProductsBlockPreview extends React.Component {
	render() {
		return (
			<div>PREVIEWING</div>
		);
	}
}

/**
 * Register and run the products block.
 */
registerBlockType( 'woocommerce/products', {
	title: __( 'Products' ),
	icon: 'universal-access-alt', // @todo Needs a good icon.
	category: 'widgets',

	attributes: {

		/**
		 * Layout to use. 'grid' or 'list'.
		 */
		layout: {
			type: 'string',
			default: 'grid',
		},

		/**
		 * Number of columns.
		 */
		columns: {
			type: 'number',
			default: 3,
		},

		/**
		 * Number of rows.
		 */
		rows: {
			type: 'number',
			default: 1,
		},

		/**
		 * Whether to display product titles.
		 */
		display_title: {
			type: 'boolean',
			default: true,
		},

		/**
		 * Whether to display prices.
		 */
		display_price: {
			type: 'boolean',
			default: true,
		},

		/**
		 * Whether to display Add to Cart buttons.
		 */
		display_add_to_cart: {
			type: 'boolean',
			default: false,
		},

		/**
		 * Order to use for products. 'newness', 'title', or 'best-selling'.
		 */
		order: {
			type: 'string',
			default: 'newness',
		},

		/**
		 * What types of products to display. 'all', 'specific', or 'category'.
		 */
		display: {
			type: 'string',
			default: 'all',
		},

		/**
		 * Which products to display if 'display' is 'specific' or 'category'. Array of product ids or category slugs depending on setting.
		 */
		display_setting: {
			type: 'array',
			default: [],
		},

		/**
		 * Whether the block is in edit or preview mode.
		 */
		edit_mode: {
			type: 'boolean',
			default: true,
		},
	},

	/**
	 * Renders and manages the block.
	 */
	edit( props ) {
		const { attributes, className, focus, setAttributes, setFocus } = props;
		const { layout, rows, columns, display_title, display_price, display_add_to_cart, order, display, display_setting, edit_mode } = attributes;

		/**
		 * Get the components for the sidebar settings area that is rendered while focused on a Products block.
		 *
		 * @return Component
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

		/**
		 * Get the components for the toolbar area that appears on top of the block when focused.
		 *
		 * @return Component
		 */
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

			const editButton = [
				{
					icon: 'edit',
					title: __( 'Edit' ),
					onClick: () => setAttributes( { edit_mode: ! edit_mode } ),
					isActive: edit_mode,
				},
			];

			return (
				<BlockControls key="controls">
					<Toolbar controls={ layoutControls } />
					<Toolbar controls={ editButton } />
				</BlockControls>
			);
		}

		/**
		 * Get the block preview component for preview mode.
		 *
		 * @return Component
		 */
		function getPreview() {
			return <ProductsBlockPreview selected_attributes={ attributes } />;
		}

		/**
		 * Get the block edit component for edit mode.
		 *
		 * @return Component
		 */
		function getSettingsEditor() {
			return <ProductsBlockSettingsEditor
				selected_display={ display }
				update_display_callback={ ( value ) => setAttributes( { display: value } ) }
				done_callback={ () => setAttributes( { edit_mode: false } ) }
			/>;
		}

		return [
			( !! focus ) ? getInspectorControls() : null,
			( !! focus ) ? getToolbarControls() : null,
			edit_mode ? getSettingsEditor() : getPreview(),
		];
	},

	/**
	 * Save the block content in the post content.
	 *
	 * @return string
	 */
	save() {
		// @todo
		// This can either build a shortcode out of all the settings (good backwards compatibility but may need extra shortcode work)
		// Or it can return null and the html can be generated with PHP like the example at https://wordpress.org/gutenberg/handbook/blocks/creating-dynamic-blocks/
		return '[products limit="3"]';
	},
} );
