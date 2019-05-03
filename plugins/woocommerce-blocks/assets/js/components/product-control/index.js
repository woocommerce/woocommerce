/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { debounce, find } from 'lodash';
import PropTypes from 'prop-types';
import { SearchListControl } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { isLargeCatalog, getProducts } from '../utils';

class ProductControl extends Component {
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
			list: __( 'Products', 'woo-gutenberg-products-block' ),
			noItems: __(
				"Your store doesn't have any products.",
				'woo-gutenberg-products-block'
			),
			search: __(
				'Search for a product to display',
				'woo-gutenberg-products-block'
			),
			updated: __(
				'Product search results updated.',
				'woo-gutenberg-products-block'
			),
		};

		// Note: selected prop still needs to be array for SearchListControl.
		return (
			<Fragment>
				<SearchListControl
					className="woocommerce-products"
					list={ list }
					isLoading={ loading }
					isSingle
					selected={ [ find( list, { id: selected } ) ] }
					onChange={ onChange }
					onSearch={ isLargeCatalog ? this.debouncedOnSearch : null }
					messages={ messages }
				/>
			</Fragment>
		);
	}
}

ProductControl.propTypes = {
	/**
	 * Callback to update the selected products.
	 */
	onChange: PropTypes.func.isRequired,
	/**
	 * The ID of the currently selected product.
	 */
	selected: PropTypes.number.isRequired,
};

export default ProductControl;
