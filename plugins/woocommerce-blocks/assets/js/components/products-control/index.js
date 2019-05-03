/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { debounce, find } from 'lodash';
import PropTypes from 'prop-types';
import { SearchListControl } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { isLargeCatalog, getProducts } from '../utils';

class ProductsControl extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			list: [],
			loading: true,
		};

		this.debouncedOnSearch = debounce( this.onSearch.bind( this ), 400 );
	}

	componentDidMount() {
		const { selected } = this.props;

		getProducts( { selected } )
			.then( ( list ) => {
				this.setState( { list, loading: false } );
			} )
			.catch( () => {
				this.setState( { list: [], loading: false } );
			} );
	}

	onSearch( search ) {
		const { selected } = this.props;
		getProducts( { selected, search } )
			.then( ( list ) => {
				this.setState( { list, loading: false } );
			} )
			.catch( () => {
				this.setState( { list: [], loading: false } );
			} );
	}

	render() {
		const { list, loading } = this.state;
		const { onChange, selected } = this.props;

		const messages = {
			clear: __( 'Clear all products', 'woo-gutenberg-products-block' ),
			list: __( 'Products', 'woo-gutenberg-products-block' ),
			noItems: __(
				"Your store doesn't have any products.",
				'woo-gutenberg-products-block'
			),
			search: __(
				'Search for products to display',
				'woo-gutenberg-products-block'
			),
			selected: ( n ) =>
				sprintf(
					_n(
						'%d product selected',
						'%d products selected',
						n,
						'woo-gutenberg-products-block'
					),
					n
				),
			updated: __(
				'Product search results updated.',
				'woo-gutenberg-products-block'
			),
		};

		return (
			<Fragment>
				<SearchListControl
					className="woocommerce-products"
					list={ list }
					isLoading={ loading }
					selected={ selected.map( ( id ) => find( list, { id } ) ).filter( Boolean ) }
					onSearch={ isLargeCatalog ? this.debouncedOnSearch : null }
					onChange={ onChange }
					messages={ messages }
				/>
			</Fragment>
		);
	}
}

ProductsControl.propTypes = {
	/**
	 * Callback to update the selected products.
	 */
	onChange: PropTypes.func.isRequired,
	/**
	 * The list of currently selected IDs.
	 */
	selected: PropTypes.array.isRequired,
};

export default ProductsControl;
