const { __ } = wp.i18n;
const { registerBlockType, InspectorControls, BlockControls } = wp.blocks;
const { Toolbar, withAPIData, Dropdown } = wp.components;
const { RangeControl, ToggleControl, SelectControl } = InspectorControls;

import { ProductsSpecificSelect } from './views/specific-select.jsx';
import { ProductsCategorySelect } from './views/category-select.jsx';
import { ProductsAttributeSelect } from './views/attribute-select.jsx';

/**
 * A setting has the following properties:
 *    title - Display title of the setting.
 *    description - Display description of the setting.
 *    value - Display setting slug to set when selected.
 *    group_container - (optional) If set the setting is a parent container.
 */
const PRODUCTS_BLOCK_DISPLAY_SETTINGS = {
	'specific' : {
		title: __( 'Individual products' ),
		description: __( 'Hand-pick which products to display' ),
		value: 'specific',
	},
	'category' : {
		title: __( 'Product category' ),
		description: __( 'Display products from a specific category or multiple categories' ),
		value: 'category',
	},
	'filter' : {
		title: __( 'Filter products' ),
		description: __( 'E.g. featured products, or products with a specific attribute like size or color' ),
		value: 'filter',
		group_container: 'filter'
	},
	'featured' : {
		title: __( 'Featured products' ),
		description: '',
		value: 'featured',
	},
	'best_sellers' : {
		title: __( 'Best sellers' ),
		description: '',
		value: 'best_sellers',
	},
	'best_rated' : {
		title: __( 'Best rated' ),
		description: '',
		value: 'best_rated',
	},
	'on_sale' : {
		title: __( 'On sale' ),
		description: '',
		value: 'on_sale',
	},
	'attribute' : {
		title: __( 'Attribute' ),
		description: '',
		value: 'attribute',
	},
	'all' : {
		title: __( 'All products' ),
		description: __( 'Display all products ordered chronologically' ),
		value: 'all',
	}
};

/**
 * One option from the list of all available ways to display products.
 */
class ProductsBlockSettingsEditorDisplayOption extends React.Component {
	render() {
		return (
			<div className={ 'wc-products-display-option value-' + this.props.value } onClick={ () => { this.props.update_display_callback( this.props.value ) } } >
				<h4>{ this.props.title }</h4>
				<p>{ this.props.description }</p>
			</div>
		);
	}
}

/**
 * A list of all available ways to display products.
 */
class ProductsBlockSettingsEditorDisplayOptions extends React.Component {

	render() {
		let classes = 'display-settings-container';
		if ( this.props.existing ) {
			classes += ' existing';
		}

		let display_settings = [];
		for ( var setting_key in PRODUCTS_BLOCK_DISPLAY_SETTINGS ) {
			display_settings.push( <ProductsBlockSettingsEditorDisplayOption { ...PRODUCTS_BLOCK_DISPLAY_SETTINGS[ setting_key ] } update_display_callback={ this.props.update_display_callback } /> );
		}

		let description = null;
		if ( ! this.props.existing ) {
			description = <p>{ __( 'Choose which products you\'d like to display:' ) }</p>;
		}

		return (
			<div className={ classes }>
				{ description }
				{ display_settings }
			</div>
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
			display: props.selected_display,
			menu_visible: props.selected_display ? false : true,
			expanded_group: '',
		}

		this.updateDisplay = this.updateDisplay.bind( this );
	}

	/**
	 * Update the display settings for the block.
	 *
	 * @param value String
	 */
	updateDisplay( value ) {

		// If not a group update display.
		let new_state = {
			display: value,
			menu_visible: false,
			expanded_group: '',
		};

		const is_group = 'undefined' !== PRODUCTS_BLOCK_DISPLAY_SETTINGS[ value ].group_container && PRODUCTS_BLOCK_DISPLAY_SETTINGS[ value ].group_container;

		if ( is_group ) {
			// If the group has not been expanded, expand it.
			new_state = {
				menu_visible: true,
				expanded_group: value,
			}

			// If the group has already been expanded, collapse it.
			if ( this.state.expanded_group === PRODUCTS_BLOCK_DISPLAY_SETTINGS[ value ].group_container ) {
				new_state.expanded_group = '';
			}
		}

		this.setState( new_state );

		// Only update the display setting if a non-group setting was selected.
		if ( ! is_group ) {
			this.props.update_display_callback( value );
		}
	}

	/**
	 * Render the display settings dropdown and any extra contextual settings.
	 */
	render() {
		let extra_settings = null;
		if ( 'specific' === this.state.display ) {
			extra_settings = <ProductsSpecificSelect { ...this.props } />;
		} else if ( 'category' === this.state.display ) {
			extra_settings = <ProductsCategorySelect { ...this.props } />;
		} else if ( 'attribute' === this.state.display ) {
			extra_settings = <ProductsAttributeSelect />
		}

		const menu = this.state.menu_visible ? <ProductsBlockSettingsEditorDisplayOptions existing={ this.state.display ? true : false } update_display_callback={ this.updateDisplay } /> : null;

		let heading = null;
		if ( this.state.display ) {
			var menu_link = <a onClick={ () => { this.setState( { menu_visible: true } ) } }>{ __( 'Display different products' ) }</a>;
			if ( this.state.menu_visible ) {
				menu_link = <a onClick={ () => { this.setState( { menu_visible: false } ) } }>{ __( 'Cancel' ) }</a>;
			}

			heading = (
				<div className="settings-heading">
					<div className="currently-displayed">
						{ __( 'Displaying ' ) }
						<strong>{ __( PRODUCTS_BLOCK_DISPLAY_SETTINGS[ this.state.display ].title ) }</strong>
					</div>
					<div className="change-display">
						{ menu_link }
					</div>
				</div>
			);
		}

		return (
			<div className={ 'wc-product-display-settings ' + ( this.state.expanded_group ? 'expanded-group-' + this.state.expanded_group : '' ) }>
				<h4>{ __( 'Products' ) }</h4>

				{ heading }

				{ menu }

				{ extra_settings }

				<div className="block-footer">
					<button type="button" className="button button-large" onClick={ this.props.done_callback }>{ __( 'Done' ) }</button>
				</div>
			</div>
		);
	}
}

/**
 * One product in the product block preview.
 */
class ProductPreview extends React.Component {

	render() {
		const { attributes, product } = this.props;

		let image = null;
		if ( product.images.length ) {
			image = <img src={ product.images[0].src } />
		}

		let title = null;
		if ( attributes.display_title ) {
			title = <div className="product-title">{ product.name }</div>
		}

		let price = null;
		if ( attributes.display_price ) {
			price = <div className="product-price">{ product.price }</div>
		}

		let add_to_cart = null;
		if ( attributes.display_add_to_cart ) {
			add_to_cart = <span className="product-add-to-cart">{ __( 'Add to cart' ) }</span>
		}

		return (
			<div className="product-preview">
				{ image }
				{ title }
				{ price }
				{ add_to_cart }
			</div>
		);
	}
}

/**
 * Renders a preview of what the block will look like with current settings.
 */
const ProductsBlockPreview = withAPIData( ( { attributes } ) => {

	const { columns, rows, order, display, display_setting, layout } = attributes;

	let query = {
		per_page: ( 'list' === layout ) ? rows : rows * columns,
		orderby: order
	};

	// @todo These will likely need to be modified to work with the final version of the category/product picker attributes.
	if ( 'specific' === display ) {
		query.include = JSON.stringify( display_setting );
		query.orderby = 'include';
	} else if ( 'category' === display ) {
		query.category = display_setting.join( ',' );
	}

	let query_string = '?';
	for ( const key of Object.keys( query ) ) {
		query_string += key + '=' + query[ key ] + '&';
	}

	return {
		products: '/wc/v2/products' + query_string
	};

} )( ( { products, attributes } ) => {

	if ( ! products.data ) {
		return __( 'Loading' );
	}

	if ( 0 === products.data.length ) {
		return __( 'No products found' );
	}

	const classes = "wc-products-block-preview " + attributes.layout + " cols-" + attributes.columns;

	return (
		<div className={ classes }>
			{ products.data.map( ( product ) => (
				<ProductPreview key={ product.id } product={ product } attributes={ attributes } />
			) ) }
		</div>
	);
} );

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
		 * Order to use for products. 'date', or 'title'.
		 */
		order: {
			type: 'string',
			default: 'date',
		},

		/**
		 * What types of products to display. 'all', 'specific', or 'category'.
		 */
		display: {
			type: 'string',
			default: '',
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
								value: 'date',
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
			return <ProductsBlockPreview attributes={ attributes } />;
		}

		/**
		 * Get the block edit component for edit mode.
		 *
		 * @return Component
		 */
		function getSettingsEditor() {
			return (
				<ProductsBlockSettingsEditor
					selected_display={ display }
					selected_display_setting={ display_setting }
					update_display_callback={ ( value ) => setAttributes( { display: value } ) }
					update_display_setting_callback={ ( value ) => setAttributes( { display_setting: value } ) }
					done_callback={ () => setAttributes( { edit_mode: false } ) }
				/>
			);
		}

		return [
			( !! focus ) ? getInspectorControls() : null,
			( !! focus ) ? getToolbarControls() : null,
			edit_mode ? getSettingsEditor() : getPreview(),
		];
	},

	/**
	 * Save the block content in the post content. Block content is saved as a products shortcode.
	 *
	 * @return string
	 */
	save( props ) {
		const { layout, rows, columns, display_title, display_price, display_add_to_cart, order, display, display_setting, className } = props.attributes;

		let shortcode_atts = new Map();
		shortcode_atts.set( 'orderby', order );
		shortcode_atts.set( 'limit', 'grid' === layout ? rows * columns : rows );
		shortcode_atts.set( 'class', 'list' === layout ? className + ' list-layout' : className );

		if ( 'grid' === layout ) {
			shortcode_atts.set( 'columns', columns );
		}

		if ( ! display_title ) {
			shortcode_atts.set( 'show_title', 0 );
		}

		if ( ! display_price ) {
			shortcode_atts.set( 'show_price', 0 );
		}

		if ( ! display_add_to_cart ) {
			shortcode_atts.set( 'show_add_to_cart', 0 );
		}

		if ( 'specific' === display ) {
			shortcode_atts.set( 'include', display_setting.join( ',' ) );
		}

		if ( 'category' === display ) {
			shortcode_atts.set( 'category', display_setting.join( ',' ) );
		}

		// Build the shortcode string out of the set shortcode attributes.
		let shortcode = '[products';
		for ( let [key, value] of shortcode_atts ) {
			shortcode += ' ' + key + '="' + value + '"';
		}
		shortcode += ']';

		return shortcode;
	},
} );