const { __ } = wp.i18n;
const { Toolbar, withAPIData, Dropdown } = wp.components;

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

	addProduct( id ) {
		let selectedProducts = this.state.selectedProducts;
		selectedProducts.push( id );

		this.setState( {
			selectedProducts: selectedProducts
		} );

		this.props.update_display_setting_callback( selectedProducts );
	}

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

	render() {
		return (
			<div className="product-specific-select">
				<ProductsSpecificSearchField addProductCallback={ this.addProduct.bind( this ) } />
				<ProductSpecificSelectedProducts products={ this.state.selectedProducts } removeProductCallback={ this.removeProduct.bind( this ) } />
			</div>
		);
	}
}

/**
 * Product search area
 */
class ProductsSpecificSearchField extends React.Component {

	constructor( props ) {
		super( props );

		this.state = {
			searchText: '',
		}

		this.updateSearchResults = this.updateSearchResults.bind( this );
	}

	updateSearchResults( evt ) {
		this.setState( {
			searchText: evt.target.value,
		} );
	}

	render() {
		return (
			<div className="product-search">
				<input type="text" value={ this.state.searchText } placeholder={ __( 'Search for products to display' ) } onChange={ this.updateSearchResults } />
				<span className="cancel" onClick={ () => { this.setState( { searchText: '' } ); } } >X</span>
				<ProductSpecificSearchResults searchString={ this.state.searchText } addProductCallback={ this.props.addProductCallback } />
			</div>
		);
	}
}

/**
 * Product search results based on the text entered into the textbox.
 */
const ProductSpecificSearchResults = withAPIData( ( props ) => {

		if ( ! props.searchString.length ) {
			return {
				products: []
			};
		}

		return {
			products: '/wc/v2/products?per_page=10&search=' + props.searchString
		};
	} )( ( { products, addProductCallback } ) => {
		if ( ! products.data ) {
			return null;
		}

		if ( 0 === products.data.length ) {
			return __( 'No products found' );
		}

		return (
			<div role="menu" className="product-search-results" aria-orientation="vertical" aria-label="{ __( 'Products list' ) }">
				<ul>
					{ products.data.map( ( product ) => (
						<li>
							<button type="button" className="components-button" id={ 'product-' + product.id } onClick={ function() { addProductCallback( product.id ) } } >
								<img src={ product.images[0].src } width="30px" /> 
								<span className="product-name">{ product.name }</span>
								<a>{ __( 'Add' ) }</a>
							</button>
						</li>
					) ) }
				</ul>
			</div>
		);
	}
);

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
