const { __ } = wp.i18n;
const { Component, RawHTML } = wp.element;
const { registerBlockType } = wp.blocks;
const { InspectorControls, BlockControls } = wp.editor;
const { Toolbar, Button, Dashicon, RangeControl, Tooltip, SelectControl } = wp.components;
const { apiFetch } = wp;

import '../../css/products-block.scss';

import { ProductsSpecificSelect } from './views/specific-select.jsx';
import { ProductsCategorySelect } from './views/category-select.jsx';
import { ProductsAttributeSelect, getAttributeSlug, getAttributeID } from './views/attribute-select.jsx';

/**
 * A setting has the following properties:
 *    title - Display title of the setting.
 *    description - Display description of the setting.
 *    value - Display setting slug to set when selected.
 *    group_container - (optional) If set the setting is a parent container.
 *    no_orderby - (optional) If set the setting does not allow orderby settings.
 */
const PRODUCTS_BLOCK_DISPLAY_SETTINGS = {
	specific: {
		title: __( 'Individual products' ),
		description: __( 'Hand-pick which products to display' ),
		value: 'specific',
	},
	category: {
		title: __( 'Product category' ),
		description: __( 'Display products from a specific category or multiple categories' ),
		value: 'category',
	},
	filter: {
		title: __( 'Filter products' ),
		description: __( 'E.g. featured products, or products with a specific attribute like size or color' ),
		value: 'filter',
		group_container: 'filter',
	},
	featured: {
		title: __( 'Featured products' ),
		description: '',
		value: 'featured',
	},
	on_sale: {
		title: __( 'On sale' ),
		description: '',
		value: 'on_sale',
	},
	best_selling: {
		title: __( 'Best sellers' ),
		description: '',
		value: 'best_selling',
		no_orderby: true,
	},
	top_rated: {
		title: __( 'Top rated' ),
		description: '',
		value: 'top_rated',
		no_orderby: true,
	},
	attribute: {
		title: __( 'Attribute' ),
		description: '',
		value: 'attribute',
	},
	all: {
		title: __( 'All products' ),
		description: __( 'Display all products ordered chronologically, alphabetically, by price, by rating or by sales' ),
		value: 'all',
	},
};

/**
 * Returns whether or not a display scope supports orderby options.
 *
 * @param string display The display scope slug.
 * @return bool
 */
function supportsOrderby( display ) {
	return ! ( PRODUCTS_BLOCK_DISPLAY_SETTINGS.hasOwnProperty( display ) &&
	PRODUCTS_BLOCK_DISPLAY_SETTINGS[ display ].hasOwnProperty( 'no_orderby' ) &&
	PRODUCTS_BLOCK_DISPLAY_SETTINGS[ display ].no_orderby );
}

/**
 * One option from the list of all available ways to display products.
 */
class ProductsBlockSettingsEditorDisplayOption extends Component {
	render() {
		let icon = 'arrow-right-alt2';

		if ( 'filter' === this.props.value && this.props.extended ) {
			icon = 'arrow-down-alt2';
		}

		let classes = 'wc-products-display-options__option wc-products-display-options__option--' + this.props.value;

		if ( this.props.current === this.props.value ) {
			icon = 'yes';
			classes += ' wc-products-display-options__option--current';
		}

		/* eslint-disable jsx-a11y/click-events-have-key-events */
		/* eslint-disable jsx-a11y/no-static-element-interactions */
		return (
			<div className={ classes } onClick={ () => {
				if ( this.props.current !== this.props.value ) {
					this.props.update_display_callback( this.props.value );
				}
			} } >
				<div className="wc-products-display-options__option-content">
					<span className="wc-products-display-options__option-title">{ this.props.title }</span>
					<p className="wc-products-display-options__option-description">{ this.props.description }</p>
				</div>
				<div className="wc-products-display-options__icon">
					<Dashicon icon={ icon } />
				</div>
			</div>
		);
		/* eslint-enable */
	}
}

/**
 * A list of all available ways to display products.
 */
class ProductsBlockSettingsEditorDisplayOptions extends Component {
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
	handleClickOutside( event ) {
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

		const display_settings = [];
		for ( const setting_key in PRODUCTS_BLOCK_DISPLAY_SETTINGS ) {
			display_settings.push( <ProductsBlockSettingsEditorDisplayOption { ...PRODUCTS_BLOCK_DISPLAY_SETTINGS[ setting_key ] } update_display_callback={ this.props.update_display_callback } extended={ this.props.extended } current={ this.props.current } key={ setting_key } /> );
		}

		const arrow = <span className="wc-products-display-options--popover__arrow"></span>;
		const description = <p className="wc-products-block-description">{ __( 'Choose which products you\'d like to display:' ) }</p>;

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
class ProductsBlockSettingsEditor extends Component {
	/**
	 * Constructor.
	 */
	constructor( props ) {
		super( props );
		this.state = {
			display: props.selected_display,
			menu_visible: props.selected_display ? false : true,
			expanded_group: '',
		};

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
			};

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
			menu_visible: false,
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
			extra_settings = <ProductsAttributeSelect { ...this.props } />;
		}

		const menu = this.state.menu_visible ? <ProductsBlockSettingsEditorDisplayOptions extended={ this.state.expanded_group ? true : false } existing={ this.state.display ? true : false } current={ this.state.display } closeMenu={ this.closeMenu } update_display_callback={ this.updateDisplay } /> : null;

		let heading = null;
		if ( this.state.display ) {
			const group_options = [ 'featured', 'on_sale', 'attribute', 'best_selling', 'top_rated' ];
			const should_group_expand = group_options.includes( this.state.display ) ? this.state.display : '';
			const menu_link = (
				<button
					type="button"
					className="wc-products-settings-heading__change-button button-link"
					onClick={ () => {
						this.setState( {
							menu_visible: ! this.state.menu_visible,
							expanded_group: should_group_expand,
						} );
					} }
				>
					{ __( 'Display different products' ) }
				</button>
			);

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
		if ( [ '', 'specific', 'category', 'attribute' ].includes( this.state.display ) && ! this.props.selected_display_setting.length ) {
			const done_tooltips = {
				'': __( 'Please select which products you\'d like to display' ),
				specific: __( 'Please search for and select products to display' ),
				category: __( 'Please select at least one category to display' ),
				attribute: __( 'Please select an attribute' ),
			};

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
export class ProductPreview extends Component {
	render() {
		const { product } = this.props;

		let image = null;
		if ( product.images.length ) {
			image = <img src={ product.images[ 0 ].src } alt="" />;
		}

		return (
			<div className="product-preview" key={ product.id + '-preview' } >
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
class ProductsBlockPreview extends Component {
	/**
	 * Constructor
	 */
	constructor( props ) {
		super( props );
		this.state = {
			products: [],
			loaded: false,
			query: '',
		};

		this.updatePreview = this.updatePreview.bind( this );
		this.getQuery = this.getQuery.bind( this );
	}

	/**
	 * Get the preview when component is first loaded.
	 */
	componentDidMount() {
		this.updatePreview();
	}

	/**
	 * Update the preview when component is updated.
	 */
	componentDidUpdate() {
		if ( this.getQuery() !== this.state.query && this.state.loaded ) {
			this.updatePreview();
		}
	}

	/**
	 * Get the endpoint for the current state of the component.
	 *
	 * @return string
	 */
	getQuery() {
		const { columns, rows, display, display_setting, orderby } = this.props.attributes;

		const query = {
			status: 'publish',
			per_page: rows * columns,
		};

		if ( 'specific' === display ) {
			query.include = display_setting.join( ',' );
			query.per_page = display_setting.length;
		} else if ( 'category' === display ) {
			query.category = display_setting.join( ',' );
		} else if ( 'attribute' === display && display_setting.length ) {
			query.attribute = getAttributeSlug( display_setting[ 0 ] );

			if ( display_setting.length > 1 ) {
				query.attribute_term = display_setting.slice( 1 ).join( ',' );
			}
		} else if ( 'featured' === display ) {
			query.featured = 1;
		} else if ( 'on_sale' === display ) {
			query.on_sale = 1;
		}

		if ( supportsOrderby( display ) ) {
			if ( 'price_desc' === orderby ) {
				query.orderby = 'price';
				query.order = 'desc';
			} else if ( 'price_asc' === orderby ) {
				query.orderby = 'price';
				query.order = 'asc';
			} else if ( 'title' === orderby ) {
				query.orderby = 'title';
				query.order = 'asc';
			} else {
				query.orderby = orderby;
			}
		}

		let query_string = '?';
		for ( const key of Object.keys( query ) ) {
			query_string += key + '=' + query[ key ] + '&';
		}

		const endpoint = '/wc-pb/v3/products' + query_string;
		return endpoint;
	}

	/**
	 * Update the preview with the latest settings.
	 */
	updatePreview() {
		const self = this;
		const query = this.getQuery();

		self.setState( {
			loaded: false,
			query: query,
		} );

		apiFetch( { path: query } ).then( ( products ) => {
			self.setState( {
				products: products,
				loaded: true,
			} );
		} );
	}

	/**
	 * Render.
	 */
	render() {
		if ( ! this.state.loaded ) {
			return __( 'Loading' );
		}

		if ( 0 === this.state.products.length ) {
			return __( 'No products found' );
		}

		const classes = 'wc-products-block-preview cols-' + this.props.attributes.columns;
		const self = this;

		return (
			<div className={ classes }>
				{ this.state.products.map( ( product ) => (
					<ProductPreview key={ product.id } product={ product } attributes={ self.props.attributes } />
				) ) }
			</div>
		);
	}
}

/**
 * Information about current block settings rendered in the sidebar.
 */
class ProductsBlockSidebarInfo extends Component {
	/**
	 * Constructor
	 */
	constructor( props ) {
		super( props );

		this.state = {
			categoriesInfo: [],
			categoriesQuery: '',

			attributeInfo: false,
			attributeQuery: '',

			termsInfo: [],
			termsQuery: '',
		};

		this.updateInfo = this.updateInfo.bind( this );
		this.getQueries = this.getQueries.bind( this );
	}

	/**
	 * Populate info when component is first loaded.
	 */
	componentDidMount() {
		this.updateInfo();
	}

	componentDidUpdate() {
		const queries = this.getQueries();

		if ( this.state.categoriesQuery !== queries.categories ||
			this.state.attributeQuery !== queries.attribute ||
			this.state.termsQuery !== queries.terms ) {
			this.updateInfo();
		}
	}

	/**
	 * Get endpoints for the current state of the component.
	 *
	 * @return object
	 */
	getQueries() {
		const { display, display_setting } = this.props.attributes;
		const endpoints = {
			attribute: '',
			terms: '',
			categories: '',
		};

		if ( 'attribute' === display && display_setting.length ) {
			const ID = getAttributeID( display_setting[ 0 ] );
			const terms = display_setting.slice( 1 ).join( ', ' );

			endpoints.attribute = '/wc-pb/v3/products/attributes/' + ID;

			if ( terms.length ) {
				endpoints.terms = '/wc-pb/v3/products/attributes/' + ID + '/terms?include=' + terms;
			}
		} else if ( 'category' === display && display_setting.length ) {
			endpoints.categories = '/wc-pb/v3/products/categories?include=' + display_setting.join( ',' );
		}

		return endpoints;
	}

	/**
	 * Get the latest info for the sidebar information area.
	 */
	updateInfo() {
		const self = this;
		const queries = this.getQueries();

		this.setState( {
			categoriesQuery: queries.categories,
			attributeQuery: queries.attribute,
			termsQuery: queries.terms,
		} );

		if ( queries.categories.length ) {
			apiFetch( { path: queries.categories } ).then( ( categories ) => {
				self.setState( {
					categoriesInfo: categories,
				} );
			} );
		} else {
			self.setState( {
				categoriesInfo: [],
			} );
		}

		if ( queries.attribute.length ) {
			apiFetch( { path: queries.attribute } ).then( ( attribute ) => {
				self.setState( {
					attributeInfo: attribute,
				} );
			} );
		} else {
			self.setState( {
				attributeInfo: false,
			} );
		}

		if ( queries.terms.length ) {
			apiFetch( { path: queries.terms } ).then( ( terms ) => {
				self.setState( {
					termsInfo: terms,
				} );
			} );
		} else {
			self.setState( {
				termsInfo: [],
			} );
		}
	}

	/**
	 * Render.
	 */
	render() {
		let descriptions = [

			// Standard description of selected scope.
			PRODUCTS_BLOCK_DISPLAY_SETTINGS[ this.props.attributes.display ].title,
		];

		if ( this.state.categoriesInfo.length ) {
			let descriptionText = __( 'Product categories: ' );
			const categories = [];
			for ( const category of this.state.categoriesInfo ) {
				categories.push( category.name );
			}
			descriptionText += categories.join( ', ' );

			descriptions = [
				descriptionText,
			];

			// Description of attributes selected scope.
		} else if ( this.state.attributeInfo ) {
			descriptions = [
				__( 'Attribute: ' ) + this.state.attributeInfo.name,
			];

			if ( this.state.termsInfo.length ) {
				let termDescriptionText = __( 'Terms: ' );
				const terms = [];
				for ( const term of this.state.termsInfo ) {
					terms.push( term.name );
				}
				termDescriptionText += terms.join( ', ' );
				descriptions.push( termDescriptionText );
			}
		}

		return (
			<div>
				{ descriptions.map( ( description, i ) => (
					<div className="scope-description" key={ i }>{ description }</div>
				) ) }
			</div>
		);
	}
}

/**
 * The main products block UI.
 */
class ProductsBlock extends Component {
	/**
	 * Constructor.
	 */
	constructor( props ) {
		super( props );

		this.getInspectorControls = this.getInspectorControls.bind( this );
		this.getToolbarControls = this.getToolbarControls.bind( this );
		this.getBlockDescription = this.getBlockDescription.bind( this );
		this.getPreview = this.getPreview.bind( this );
		this.getSettingsEditor = this.getSettingsEditor.bind( this );
	}

	/**
	 * Get the components for the sidebar settings area that is rendered while focused on a Products block.
	 *
	 * @return Component
	 */
	getInspectorControls() {
		const { attributes, setAttributes } = this.props;
		const { rows, columns, display, orderby } = attributes;

		const columnControl = (
			<RangeControl
				label={ __( 'Columns' ) }
				value={ columns }
				onChange={ ( value ) => setAttributes( { columns: value } ) }
				min={ wc_product_block_data.min_columns }
				max={ wc_product_block_data.max_columns }
			/>
		);

		let orderControl = null;
		if ( supportsOrderby( display ) ) {
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

		// Row settings don't make sense for specific-selected products display.
		let rowControl = null;
		if ( 'specific' !== display ) {
			rowControl = (
				<RangeControl
					label={ __( 'Rows' ) }
					value={ rows }
					onChange={ ( value ) => setAttributes( { rows: value } ) }
					min={ wc_product_block_data.min_rows }
					max={ wc_product_block_data.max_rows }
				/>
			);
		}

		return (
			<InspectorControls key="inspector">
				{ this.getBlockDescription() }
				<h3>{ __( 'Layout' ) }</h3>
				{ columnControl }
				{ rowControl }
				{ orderControl }
			</InspectorControls>
		);
	}

	/**
	 * Get the components for the toolbar area that appears on top of the block when focused.
	 *
	 * @return Component
	 */
	getToolbarControls() {
		const props = this.props;
		const { attributes, setAttributes } = props;
		const { display, display_setting, edit_mode } = attributes;

		// Edit button should not do anything if valid product selection has not been made.
		const shouldDisableEditButton = [ '', 'specific', 'category', 'attribute' ].includes( display ) && ! display_setting.length;

		const editButton = [
			{
				icon: 'edit',
				title: __( 'Edit' ),
				onClick: shouldDisableEditButton ? function() {} : () => setAttributes( { edit_mode: ! edit_mode } ),
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
	 * Get a description of the current block settings.
	 *
	 * @return Component
	 */
	getBlockDescription() {
		const { attributes, setAttributes } = this.props;
		const { display } = attributes;

		if ( ! display.length ) {
			return null;
		}

		function editQuicklinkHandler() {
			setAttributes( {
				edit_mode: true,
			} );

			// @todo center in view
		}

		let editQuickLink = null;
		if ( ! attributes.edit_mode ) {
			editQuickLink = (
				<div className="wc-products-scope-description--edit-quicklink">
					<Button isLink onClick={ editQuicklinkHandler }>{ __( 'Edit' ) }</Button>
				</div>
			);
		}

		return (
			<div className="wc-products-scope-descriptions">
				<div className="wc-products-scope-details">
					<h3>{ __( 'Current Source' ) }</h3>
					<ProductsBlockSidebarInfo attributes={ attributes } />
				</div>
				{ editQuickLink }
			</div>
		);
	}

	/**
	 * Get the block preview component for preview mode.
	 *
	 * @return Component
	 */
	getPreview() {
		return <ProductsBlockPreview key="preview" attributes={ this.props.attributes } />;
	}

	/**
	 * Get the block edit component for edit mode.
	 *
	 * @return Component
	 */
	getSettingsEditor() {
		const { attributes, setAttributes } = this.props;
		const { display, display_setting } = attributes;

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
				key="settings-editor"
				attributes={ attributes }
				selected_display={ display }
				selected_display_setting={ display_setting }
				update_display_callback={ update_display_callback }
				update_display_setting_callback={ ( value ) => setAttributes( { display_setting: value } ) }
				done_callback={ () => setAttributes( { edit_mode: false } ) }
			/>
		);
	}

	render() {
		const { attributes } = this.props;
		const { edit_mode } = attributes;

		return [
			this.getInspectorControls(),
			this.getToolbarControls(),
			edit_mode ? this.getSettingsEditor() : this.getPreview(),
		];
	}
}

/**
 * Register and run the products block.
 */
registerBlockType( 'woocommerce/products', {
	title: __( 'Products' ),
	icon: 'screenoptions',
	category: 'widgets',
	description: __( 'Display a grid of products from a variety of sources.' ),

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
			default: wc_product_block_data.default_rows,
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
		return <ProductsBlock { ...props } />;
	},

	/**
	 * Save the block content in the post content. Block content is saved as a products shortcode.
	 *
	 * @return string
	 */
	save( props ) {
		const { rows, columns, display, display_setting, orderby } = props.attributes;

		const shortcode_atts = new Map();
		if ( 'specific' !== display ) {
			shortcode_atts.set( 'limit', rows * columns );
		}
		shortcode_atts.set( 'columns', columns );

		if ( 'specific' === display ) {
			shortcode_atts.set( 'ids', display_setting.join( ',' ) );
		} else if ( 'category' === display ) {
			shortcode_atts.set( 'category', display_setting.join( ',' ) );
		} else if ( 'featured' === display ) {
			shortcode_atts.set( 'visibility', 'featured' );
		} else if ( 'on_sale' === display ) {
			shortcode_atts.set( 'on_sale', '1' );
		} else if ( 'best_selling' === display ) {
			shortcode_atts.set( 'best_selling', '1' );
		} else if ( 'top_rated' === display ) {
			shortcode_atts.set( 'top_rated', '1' );
		} else if ( 'attribute' === display ) {
			const attribute = display_setting.length ? getAttributeSlug( display_setting[ 0 ] ) : '';
			const terms = display_setting.length > 1 ? display_setting.slice( 1 ).join( ',' ) : '';

			shortcode_atts.set( 'attribute', attribute );
			if ( terms.length ) {
				shortcode_atts.set( 'terms', terms );
			}
		}

		if ( supportsOrderby( display ) ) {
			if ( 'price_desc' === orderby ) {
				shortcode_atts.set( 'orderby', 'price' );
				shortcode_atts.set( 'order', 'DESC' );
			} else if ( 'price_asc' === orderby ) {
				shortcode_atts.set( 'orderby', 'price' );
				shortcode_atts.set( 'order', 'ASC' );
			} else if ( 'date' === orderby ) {
				shortcode_atts.set( 'orderby', 'date' );
				shortcode_atts.set( 'order', 'DESC' );
			} else {
				shortcode_atts.set( 'orderby', orderby );
			}
		}

		// Build the shortcode string out of the set shortcode attributes.
		let shortcode = '[products';
		for ( const [ key, value ] of shortcode_atts ) {
			shortcode += ' ' + key + '="' + value + '"';
		}
		shortcode += ']';

		return <RawHTML>{ shortcode }</RawHTML>;
	},
} );
