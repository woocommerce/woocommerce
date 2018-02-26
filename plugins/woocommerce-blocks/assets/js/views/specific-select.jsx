const { __ } = wp.i18n;
const { Toolbar, withAPIData, Dropdown } = wp.components;
const { TransitionGroup, CSSTransition } = ReactTransitionGroup;

/**
 * When the display mode is 'Specific products' search for and add products to the block.
 *
 * @todo Add the functionality and everything.
 */
export class ProductsSpecificSelect extends React.Component {

	/**
	 * Constructor.
	 */
	constructor( props ) {
		super( props );

		this.state = {
			selectedProducts: props.selected_display_setting || [],
		}
	}

	/**
	 * Add a product to the list of selected products.
	 *
	 * @param id int Product ID.
	 */
	addProduct( id ) {

		let selectedProducts = this.state.selectedProducts;
		selectedProducts.push( id );

		this.setState( {
			selectedProducts: selectedProducts
		} );

		/**
		 * We need to copy the existing data into a new array.
		 * We can't just push the new product onto the end of the existing array because Gutenberg seems 
		 * to do some sort of check by reference to determine whether to *actually* update the attribute
		 * and will not update it if we just pass back the same array with an extra element on the end.
		 */
		this.props.update_display_setting_callback( selectedProducts.slice() );
	}

	/**
	 * Remove a product from the list of selected products.
	 *
	 * @param id int Product ID.
	 */
	removeProduct( id ) {
		let oldProducts = this.state.selectedProducts;
		let newProducts = [];

		for ( let productId of oldProducts ) {
			if ( productId !== id ) {
				newProducts.push( productId );
			}
		}

		this.setState( {
			selectedProducts: newProducts
		} );

		this.props.update_display_setting_callback( newProducts );
	}

	/**
	 * Render the product specific select screen.
	 */
	render() {
		return (
			<div className="product-specific-select">
				<ProductsSpecificSearchField 
					addProductCallback={ this.addProduct.bind( this ) } 
					selectedProducts={ this.state.selectedProducts } 
				/>
				<ProductSpecificSelectedProducts 
					products={ this.state.selectedProducts } 
					removeProductCallback={ this.removeProduct.bind( this ) } 
				/>
			</div>
		);
	}
}

/**
 * Product search area
 */
class ProductsSpecificSearchField extends React.Component {

	/**
	 * Constructor.
	 */
	constructor( props ) {
		super( props );

		this.state = {
			searchText: '',
		}

		this.updateSearchResults = this.updateSearchResults.bind( this );
		this.setWrapperRef = this.setWrapperRef.bind( this );
		this.handleClickOutside = this.handleClickOutside.bind( this );
	}

	/**
	 * Hook in the listener for closing menu when clicked outside.
	 */
	componentDidMount() {
		document.addEventListener( 'mousedown', this.handleClickOutside );
	}

	/**
	 * Remove the listener for closing menu when clicked outside.
	 */
	componentWillUnmount() {
		document.removeEventListener( 'mousedown', this.handleClickOutside );
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
        if ( this.wrapperRef && ! this.wrapperRef.contains( event.target ) ) {
            this.setState( {
            	searchText: '',
            } );
        }
	}

	/**
	 * Event handler for updating results when text is typed into the input.
	 *
	 * @param evt Event object.
	 */
	updateSearchResults( evt ) {
		this.setState( {
			searchText: evt.target.value,
		} );
	}

	/**
	 * Render the product search UI.
	 */
	render() {

		let cancelButton = null;
		if ( this.state.searchText.length ) {
			cancelButton = <span className="cancel" onClick={ () => { this.setState( { searchText: '' } ); } } >X</span>;
		}

		return (
			<div className="product-search" ref={ this.setWrapperRef }>
				<input type="text"
					value={ this.state.searchText }
					placeholder={ __( 'Search for products to display' ) }
					onChange={ this.updateSearchResults }
				/>
				{ cancelButton }
				<ProductSpecificSearchResults
					searchString={ this.state.searchText }
					addProductCallback={ this.props.addProductCallback }
					selectedProducts={ this.props.selectedProducts }
				/>
			</div>
		);
	}
}

/**
 * Render product search results based on the text entered into the textbox.
 */
const ProductSpecificSearchResults = withAPIData( ( props ) => {

		if ( ! props.searchString.length ) {
			return {
				products: []
			};
		}

		return {
			products: '/wc/v2/products?per_page=10&search=' + props.searchString,
		};
	} )( ( { products, addProductCallback, selectedProducts } ) => {
		if ( ! products.data ) {
			return null;
		}

		if ( 0 === products.data.length ) {
			return __( 'No products found' );
		}

		return <ProductSpecificSearchResultsDropdown 
			products={ products.data }
			addProductCallback={ addProductCallback }
			selectedProducts={ selectedProducts }
		/>
	}
);

/**
 * The dropdown of search results.
 */
class ProductSpecificSearchResultsDropdown extends React.Component {

	/**
	 * Render dropdown.
	 */
	render() {
		const { products, addProductCallback, selectedProducts } = this.props;

		let productElements = [];

		for ( let product of products ) {
			if ( selectedProducts.includes( product.id ) ) {
				continue;
			}

			productElements.push(
				<CSSTransition
					key={ product.slug }
					classNames="components-button--transition"
					timeout={ { exit: 700 } }
				>
					<ProductSpecificSearchResultsDropdownElement 
						product={product} 
						addProductCallback={ addProductCallback } 
					/>
				</CSSTransition>
			);
		}

		return (
			<div role="menu" className="product-search-results" aria-orientation="vertical" aria-label="{ __( 'Products list' ) }">
				<ul>
					<TransitionGroup>
						{ productElements }
					</TransitionGroup>
				</ul>
			</div>
		);
	}
}

/**
 * One search result.
 */
class ProductSpecificSearchResultsDropdownElement extends React.Component {

	/**
	 * Constructor.
	 */
	constructor( props ) {
		super( props );

		this.state = {
			clicked: false,
		}

		this.handleClick = this.handleClick.bind( this );
	}

	/**
	 * Add product to main list and change UI to show it was added.
	 */
	handleClick() {
		this.setState( { clicked: true } );
		this.props.addProductCallback( this.props.product.id );
	}

	/**
	 * Render one result in the search results.
	 */
	render() {
		const product = this.props.product;

		return (
			<li>
				<button type="button" 
					className='components-button'
					id={ 'product-' + product.id } 
					onClick={ this.handleClick } >
						<img src={ product.images[0].src } width="30px" /> 
						<span className="product-name">{ this.state.clicked ? __( 'Added' ) : product.name }</span>
						<a>{ __( 'Add' ) }</a>
				</button>
			</li>
		);
	}
}

/**
 * List preview of selected products.
 */
const ProductSpecificSelectedProducts = withAPIData( ( props ) => {

		if ( ! props.products.length ) {
			return {
				products: []
			};
		}

		return {
			products: '/wc/v2/products?include=' + props.products.join( ',' )
		};
	} )( ( { products, removeProductCallback } ) => {
		if ( ! products.data ) {
			return null;
		}

		if ( 0 === products.data.length ) {
			return __( 'No products selected' );
		}

		return (
			<div role="menu" className="selected-products" aria-orientation="vertical" aria-label="{ __( 'Products list' ) }">
				<ul>
					{ products.data.map( ( product ) => (
						<li>
							<button type="button" className="components-button" id={ 'product-' + product.id } >
								<img src={ product.images[0].src } width="30px" /> 
								<span className="product-name">{ product.name }</span>
								<a onClick={ function() { removeProductCallback( product.id ) } } >{ __( 'X' ) }</a>
							</button>
						</li>
					) ) }
				</ul>
			</div>
		);
	}
);
