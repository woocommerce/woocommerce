/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import { Component, Fragment } from '@wordpress/element';
import { find } from 'lodash';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import SearchListControl from '../search-list-control';

class ProductsControl extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			list: [],
			loading: true,
		};
	}

	componentDidMount() {
		apiFetch( {
			path: addQueryArgs( '/wc-pb/v3/products', { per_page: -1, status: 'publish' } ),
		} )
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
