const { __ } = wp.i18n;
const { registerBlockType, InspectorControls, BlockControls } = wp.blocks;
const { Toolbar, withAPIData } = wp.components;
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
 * @todo Save data
 */
class ProductsCategorySelect extends React.Component {

	/**
	 * Constructor.
	 */
	constructor( props ) {
		super( props );

		this.state = {
			selectedCategories: props.selected_display_setting,
			filterQuery: ''
		}

		this.checkboxChange = this.checkboxChange.bind( this );
		this.filterResults = this.filterResults.bind( this );
	}

	/**
	 * Handle checkbox toggle.
	 *
	 * @param Event object evt
	 */
	checkboxChange( evt ) {
		let selectedCategories = this.state.selectedCategories;

		if ( evt.target.checked && ! selectedCategories.includes( parseInt( evt.target.value, 10 ) ) ) {
			selectedCategories.push( parseInt( evt.target.value, 10 ) );
		} else if ( ! evt.target.checked ) {
			selectedCategories = selectedCategories.filter( category => category !== parseInt( evt.target.value, 10 ) );
		}

		this.setState( {
			selectedCategories: selectedCategories
		} );

		this.props.update_display_setting_callback( selectedCategories );
	}

	/**
	 * Filter categories.
	 *
	 * @param Event object evt
	 */
	filterResults( evt ) {
		this.setState( {
			filterQuery: evt.target.value
		} );
	}

	/**
	 * Render the list of categories and the search input.
	 */
	render() {
		return (
			<div className="product-category-select">
				<ProductCategoryFilter filterResults={ this.filterResults } />
				<ProductCategoryList filterQuery={ this.state.filterQuery } selectedCategories={ this.state.selectedCategories } checkboxChange={ this.checkboxChange } />
			</div>
		);
	}
}

/**
 * The category search input.
 */
const ProductCategoryFilter = ( { filterResults } ) => {
	return (
		<div>
			<input id="product-category-search" type="search" placeholder={ __( 'Search for categories' ) } onChange={ filterResults } />
		</div>
	);
}

/**
 * Fetch and build a tree of product categories.
 */
const ProductCategoryList = withAPIData( ( props ) => {
		return {
			categories: '/wc/v2/products/categories'
		};
	} )( ( { categories, filterQuery, selectedCategories, checkboxChange } ) => {
		if ( ! categories.data ) {
			return __( 'Loading' );
		}

		if ( 0 === categories.data.length ) {
			return __( 'No categories found' );
		}

		const CategoryTree = ( { categories, parent } ) => {
			let filteredCategories = categories.filter( ( category ) => category.parent === parent );

			return ( filteredCategories.length > 0 ) && (
				<ul>
					{ filteredCategories.map( ( category ) => (
						<li>
							<label htmlFor={ 'product-category-' + category.id }>
								<input type="checkbox"
								       id={ 'product-category-' + category.id }
								       value={ category.id }
								       checked={ selectedCategories.includes( category.id ) }
								       onChange={ checkboxChange }
								/> { category.name }
							</label>
							<CategoryTree categories={ categories } parent={ category.id } />
						</li>
					) ) }
				</ul>
			);
		};

		let categoriesData = categories.data;

		if ( '' !== filterQuery ) {
			categoriesData = categoriesData.filter( category => category.slug.includes( filterQuery.toLowerCase() ) );
		}

		return (
			<div>
				<CategoryTree categories={ categoriesData } parent={ 0 } />
			</div>
		);
	}
);

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
			extra_settings = <ProductsCategorySelect { ...this.props } />;
		}

		return (
			<div className="wc-product-display-settings">

				<h4>{ __( 'Products' ) }</h4>

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
		per_page: ( 'list' === layout ) ? columns : rows * columns,
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
				<ProductPreview product={ product } attributes={ attributes } />
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
