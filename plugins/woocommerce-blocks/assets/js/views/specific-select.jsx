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
			selectedProducts: props.selected_display_setting,
		}
	}

	selectProduct( evt ) {
		evt.preventDefault();

		let selectProduct = this.state.selectProduct;

		this.setState( {
			selectProduct: selectProduct
		} );
	}

	render() {
		return (
			<div className="product-specific-select">
				<div className="add-new">
					<Dropdown
						className="my-container-class-name"
						contentClassName="my-popover-content-classname"
						position="bottom right"
						renderToggle={ ( { isOpen, onToggle } ) => (
							<button className="button button-large" onClick={ onToggle } aria-expanded={ isOpen }>
								{ __( 'Add product' ) }
							</button>
						) }
						renderContent={ () => (
							<div>
								<ProductSpecifcSearch />
							</div>
						) }
					/>
				</div>
				<ProductsSpecificList selectedProducts={ this.state.selectedProducts } />
			</div>
		);
	}
}

const ProductSpecifcSearch = withAPIData( ( props ) => {
		return {
			products: '/wc/v2/products?per_page=10'
		};
	} )( ( { products } ) => {
		if ( ! products.data ) {
			return __( 'Loading' );
		}

		if ( 0 === products.data.length ) {
			return __( 'No products found' );
		}

		const ProductsList = ( { products } ) => {
			return ( products.length > 0 ) && (
				<ul>
					{ products.map( ( product ) => (
						<li>
							<button type="button" className="components-button" id={ 'product-' + product.id }>
								<img src={ product.images[0].src } width="30px" /> { product.name }
							</button>
						</li>
					) ) }
				</ul>
			);
		};

		let productsData = products.data;

		return (
			<div role="menu" aria-orientation="vertical" aria-label="{ __( 'Products list' ) }">
				<ProductsList products={ productsData } />
			</div>
		);
	}
);

const ProductsSpecificList = ( { selectedProducts } ) => {
	if ( ! selectedProducts || 0 === selectedProducts.length ) {
		return __( 'No products selected found' );
	}

	const classes = "wc-products-block-preview";
	const attributes = {};

	return (
		<div className={ classes }>
			{ selectedProducts.data.map( ( product ) => (
				<ProductPreview product={ product } attributes={ attributes } />
			) ) }
		</div>
	);
}
