/**
 * External dependencies
 */
import { Component } from 'react';
import PropTypes from 'prop-types';
import withQueryStringValues from '@woocommerce/base-hocs/with-query-string-values';

/**
 * Internal dependencies
 */
import ProductList from './index';

class ProductListContainer extends Component {
	onPageChange = ( newPage ) => {
		this.props.updateQueryStringValues( {
			product_page: newPage,
		} );
	};

	onSortChange = ( event ) => {
		const newSortValue = event.target.value;
		this.props.updateQueryStringValues( {
			product_sort: newSortValue,
			product_page: 1,
		} );
	};

	render() {
		// eslint-disable-next-line camelcase
		const { attributes, product_page, product_sort } = this.props;
		const currentPage = parseInt( product_page );
		const sortValue = product_sort || attributes.orderby; // eslint-disable-line camelcase

		return (
			<ProductList
				attributes={ attributes }
				currentPage={ currentPage }
				onPageChange={ this.onPageChange }
				onSortChange={ this.onSortChange }
				sortValue={ sortValue }
			/>
		);
	}
}

ProductListContainer.propTypes = {
	attributes: PropTypes.object.isRequired,
	// From withQueryStringValues
	product_page: PropTypes.oneOfType( [ PropTypes.number, PropTypes.string ] ),
	product_sort: PropTypes.string,
};

ProductListContainer.defaultProps = {
	product_page: 1,
};

export default withQueryStringValues( [ 'product_page', 'product_sort' ] )(
	ProductListContainer
);
