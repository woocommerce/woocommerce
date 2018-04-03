const { __ } = wp.i18n;
const { RawHTML } = wp.element;
const { registerBlockType, InspectorControls, BlockControls } = wp.blocks;
const { Toolbar, withAPIData, Dropdown, Dashicon, RangeControl, Tooltip, SelectControl } = wp.components;

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
		let icon = 'arrow-right-alt2';

		if ( 'filter' === this.props.value && this.props.extended ) {
			icon = 'arrow-down-alt2';
		}

		let classes = 'wc-products-display-options__option wc-products-display-options__option--' + this.props.value;

		if ( this.props.current === this.props.value ) {
			icon    = 'yes';
			classes += ' wc-products-display-options__option--current';
		}

		return (
			<div className={ classes } onClick={ () => { this.props.current !== this.props.value && this.props.update_display_callback( this.props.value ) } } >
				<div className="wc-products-display-options__option-content">
					<span className="wc-products-display-options__option-title">{ this.props.title }</span>
					<p className="wc-products-display-options__option-description">{ this.props.description }</p>
				</div>
				<div className="wc-products-display-options__icon">
					<Dashicon icon={ icon } />
				</div>
			</div>
		);
	}
}

/**
 * A list of all available ways to display products.
 */
class ProductsBlockSettingsEditorDisplayOptions extends React.Component {

	/**
	 * Constructor.
	 */
	constructor( props ) {
		super( props );

		this.setWrapperRef = this.setWrapperRef.bind( this );
		this.handleClickOutside = this.handleClickOutside.bind( this );
	}

	/**
	 * Hook in the listener for closing menu when clicked outside.
	 */
	componentDidMount() {
		if ( this.props.existing ) {
			document.addEventListener( 'mousedown', this.handleClickOutside );
		}
	}

	/**
	 * Remove the listener for closing menu when clicked outside.
	 */
	componentWillUnmount() {
		if ( this.props.existing ) {
			document.removeEventListener( 'mousedown', this.handleClickOutside );
		}
	}

	/**
	 * Set the wrapper reference.
	 *
	 * @param node DOMNode
	 */
	setWrapperRef( node ) {
		this.wrapperRef = node;
	}

	/**
	 * Close the menu when user clicks outside the search area.
	 */
	handleClickOutside( evt ) {
        if ( this.wrapperRef && ! this.wrapperRef.contains( event.target ) && 'wc-products-settings-heading__change-button button-link' !== event.target.getAttribute( 'class' ) ) {
            this.props.closeMenu();
        }
	}

	/**
	 * Render the list of options.
	 */
	render() {
		let classes = 'wc-products-display-options';

		if ( this.props.extended ) {
			classes += ' wc-products-display-options--extended';
		}

		if ( this.props.existing ) {
			classes += ' wc-products-display-options--popover';
		}

		let display_settings = [];
		for ( var setting_key in PRODUCTS_BLOCK_DISPLAY_SETTINGS ) {
			display_settings.push( <ProductsBlockSettingsEditorDisplayOption { ...PRODUCTS_BLOCK_DISPLAY_SETTINGS[ setting_key ] } update_display_callback={ this.props.update_display_callback } extended={ this.props.extended } current={ this.props.current } /> );
		}

		let arrow = <span className="wc-products-display-options--popover__arrow"></span>;
		let description = <p className="wc-products-block-description">{ __( 'Choose which products you\'d like to display:' ) }</p>;

		return (
			<div className={ classes } ref={ this.setWrapperRef }>
				{ this.props.existing && arrow }
				{ ! this.props.existing && description }
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
		this.closeMenu = this.closeMenu.bind( this );
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

	closeMenu() {
		this.setState( {
			menu_visible: false
		} );
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
			extra_settings = <ProductsAttributeSelect { ...this.props } />
		}

		const menu = this.state.menu_visible ? <ProductsBlockSettingsEditorDisplayOptions extended={ this.state.expanded_group ? true : false } existing={ this.state.display ? true : false } current={ this.state.display } closeMenu={ this.closeMenu } update_display_callback={ this.updateDisplay } /> : null;

		let heading = null;
		if ( this.state.display ) {
			const group_options     = [ 'featured', 'on_sale', 'attribute' ];
			let should_group_expand = group_options.includes( this.state.display ) ? this.state.display : '';
			let menu_link           = <button type="button" className="wc-products-settings-heading__change-button button-link" onClick={ () => { this.setState( { menu_visible: ! this.state.menu_visible, expanded_group: should_group_expand } ) } }>{ __( 'Display different products' ) }</button>;

			heading = (
				<div className="wc-products-settings-heading">
					<div className="wc-products-settings-heading__current">
						{ __( 'Displaying ' ) }
						<strong>{ __( PRODUCTS_BLOCK_DISPLAY_SETTINGS[ this.state.display ].title ) }</strong>
					</div>
					<div className="wc-products-settings-heading__change">
						{ menu_link }
					</div>
				</div>
			);
		}

		let done_button = <button type="button" className="button wc-products-settings__footer-button" onClick={ this.props.done_callback }>{ __( 'Done' ) }</button>;
		if ( ['', 'specific', 'category', 'attribute'].includes( this.state.display ) && ! this.props.selected_display_setting.length ) {
			const done_tooltips = {
				'': __( 'Please select which products you\'d like to display' ),
				specific: __( 'Please search for and select products to display' ),
				category: __( 'Please select at least one category to display' ),
				attribute: __( 'Please select an attribute' ),
			}

			done_button = (
				<Tooltip text={ done_tooltips[ this.state.display ] } >
					<button type="button" className="button wc-products-settings__footer-button disabled">{ __( 'Done' ) }</button>
				</Tooltip>
			);
		}


		return (
			<div className={ 'wc-products-settings ' + ( this.state.expanded_group ? 'expanded-group-' + this.state.expanded_group : '' ) }>
				<h4 className="wc-products-settings__title"><Dashicon icon={ 'screenoptions' } /> { __( 'Products' ) }</h4>

				{ heading }

				{ menu }

				{ extra_settings }

				<div className="wc-products-settings__footer">
					{ done_button }
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

		return (
			<div className="product-preview">
				{ image }
				<div className="product-title">{ product.name }</div>
				<div className="product-price" dangerouslySetInnerHTML={ { __html: product.price_html } } />
				<span className="product-add-to-cart">{ __( 'Add to cart' ) }</span>
			</div>
		);
	}
}

/**
 * Renders a preview of what the block will look like with current settings.
 */
const ProductsBlockPreview = withAPIData( ( { attributes } ) => {

	const { columns, rows, display, display_setting, orderby } = attributes;

	let query = {
		per_page: rows * columns,
	};

	if ( 'specific' === display ) {
		query.include = display_setting.join( ',' );
		query.orderby = 'include';
	} else if ( 'category' === display ) {
		query.category = display_setting.join( ',' );
	} else if ( 'attribute' === display && display_setting.length ) {
		query.attribute = display_setting[0];

		if ( display_setting.length > 1 ) {
			query.attribute_term = display_setting.slice( 1 ).join( ',' );
		}
	} else if ( 'featured' === display ) {
		query.featured = 1;
	} else if ( 'on_sale' === display ) {
		query.on_sale = 1;
	}

	// @todo Add support for orderby by sales, rating, and price to the API.
	if ( 'specific' !== display && ( 'title' === orderby || 'date' === orderby ) ) {
		query.orderby = orderby;

		if ( 'title' === orderby ) {
			query.order = 'asc';
		}
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

	const classes = "wc-products-block-preview cols-" + attributes.columns;

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
	icon: 'screenoptions',
	category: 'widgets',

	attributes: {

		/**
		 * Number of columns.
		 */
		columns: {
			type: 'number',
			default: wc_product_block_data.default_columns,
		},

		/**
		 * Number of rows.
		 */
		rows: {
			type: 'number',
			default: 1,
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
		 * How to order the products: 'date', 'popularity', 'price_asc', 'price_desc' 'rating', 'title'.
		 */
		orderby: {
			type: 'string',
			default: 'date',
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
		const { rows, columns, display, display_setting, orderby, edit_mode } = attributes;

		/**
		 * Get the components for the sidebar settings area that is rendered while focused on a Products block.
		 *
		 * @return Component
		 */
		function getInspectorControls() {

			let columnControl = (
				<RangeControl
					label={ __( 'Columns' ) }
					value={ columns }
					onChange={ ( value ) => setAttributes( { columns: value } ) }
					min={ wc_product_block_data.min_columns }
					max={ wc_product_block_data.max_columns }
				/>
			);
			
			// Orderby settings don't make sense for specific-selected products display.
			let orderControl = null;
			if ( 'specific' !== display ) {
				orderControl = (
					<SelectControl
						key="query-panel-select"
						label={ __( 'Order Products By' ) }
						value={ orderby }
						options={ [
							{
								label: __( 'Newness - newest first' ),
								value: 'date',
							},
							{
								label: __( 'Price - low to high' ),
								value: 'price_asc',
							},
							{
								label: __( 'Price - high to low' ),
								value: 'price_desc',
							},
							{
								label: __( 'Rating - highest first' ),
								value: 'rating',
							},
							{
								label: __( 'Sales - most first' ),
								value: 'popularity',
							},
							{
								label: __( 'Title - alphabetical' ),
								value: 'title',
							},
						] }
						onChange={ ( value ) => setAttributes( { orderby: value } ) }
					/>
				);
			}

			return (
				<InspectorControls key="inspector">
					<h3>{ __( 'Layout' ) }</h3>
					{ columnControl }
					<RangeControl
						label={ __( 'Rows' ) }
						value={ rows }
						onChange={ ( value ) => setAttributes( { rows: value } ) }
						min={ 1 }
						max={ 6 }
					/>
					{ orderControl }
				</InspectorControls>
			);
		};

		/**
		 * Get the components for the toolbar area that appears on top of the block when focused.
		 *
		 * @return Component
		 */
		function getToolbarControls() {

			// Edit button should not do anything if valid product selection has not been made.
			const shouldDisableEditButton = ['', 'specific', 'category', 'attribute'].includes( display ) && ! display_setting.length;

			const editButton = [
				{
					icon: 'edit',
					title: __( 'Edit' ),
					onClick: shouldDisableEditButton ? function(){} : () => setAttributes( { edit_mode: ! edit_mode } ),
					isActive: edit_mode,
				},
			];

			return (
				<BlockControls key="controls">
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

			const update_display_callback = ( value ) => {

				// These options have setting screens that need further input from the user, so keep edit mode open.
				const needsFurtherSettings = [ 'specific', 'attribute', 'category' ];

				if ( display !== value ) {
					setAttributes( {
						display: value,
						display_setting: [],
						edit_mode: needsFurtherSettings.includes( value ),
					} );
				}
			};

			return (
				<ProductsBlockSettingsEditor
					selected_display={ display }
					selected_display_setting={ display_setting }
					update_display_callback={ update_display_callback }
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
		const { rows, columns, display, display_setting, orderby } = props.attributes;

		let shortcode_atts = new Map();
		shortcode_atts.set( 'limit', rows * columns );
		shortcode_atts.set( 'columns', columns );

		if ( 'specific' === display ) {
			shortcode_atts.set( 'ids', display_setting.join( ',' ) );
		} else if ( 'category' === display ) {
			shortcode_atts.set( 'category', display_setting.join( ',' ) );
		} else if ( 'featured' === display ) {
			shortcode_atts.set( 'visibility', 'featured' );
		} else if ( 'on_sale' === display ) {
			shortcode_atts.set( 'on_sale', '1' );
		} else if ( 'attribute' === display ) {
			const attribute = display_setting.length ? display_setting[0] : '';
			const terms = display_setting.length > 1 ? display_setting.slice( 1 ).join( ',' ) : '';

			shortcode_atts.set( 'attribute', attribute );
			if ( terms.length ) {
				shortcode_atts.set( 'terms', terms );
			}
		}

		if ( 'specific' !== display ) {
			if ( 'price_desc' === orderby ) {
				shortcode_atts.set( 'orderby', 'price' );
				shortcode_atts.set( 'order', 'DESC' )
			} else if ( 'price_asc' === orderby ) {
				shortcode_atts.set( 'orderby', 'price' );
				shortcode_atts.set( 'order', 'ASC' )
			} else {
				shortcode_atts.set( 'orderby', orderby );
			}
		}

		// Build the shortcode string out of the set shortcode attributes.
		let shortcode = '[products';
		for ( let [key, value] of shortcode_atts ) {
			shortcode += ' ' + key + '="' + value + '"';
		}
		shortcode += ']';

		return <RawHTML>{ shortcode }</RawHTML>;
	},
} );