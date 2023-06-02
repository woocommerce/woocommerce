/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { createContext, useContext } from '@wordpress/element';

/**
 * Default product shape matching API response.
 */
const defaultProductData = {
	id: 0,
	name: '',
	parent: 0,
	type: 'simple',
	variation: '',
	permalink: '',
	sku: '',
	short_description: '',
	description: '',
	on_sale: false,
	prices: {
		currency_code: 'USD',
		currency_symbol: '$',
		currency_minor_unit: 2,
		currency_decimal_separator: '.',
		currency_thousand_separator: ',',
		currency_prefix: '$',
		currency_suffix: '',
		price: '0',
		regular_price: '0',
		sale_price: '0',
		price_range: null,
	},
	price_html: '',
	average_rating: '0',
	review_count: 0,
	images: [],
	categories: [],
	tags: [],
	attributes: [],
	variations: [],
	has_options: false,
	is_purchasable: false,
	is_in_stock: false,
	is_on_backorder: false,
	low_stock_remaining: null,
	sold_individually: false,
	quantity_limit: 99,
	add_to_cart: {
		text: 'Add to cart',
		description: 'Add to cart',
		url: '',
	},
};

/**
 * This context is used to pass product data down to all children blocks in a given tree.
 *
 * @member {Object} ProductDataContext A react context object
 */
const ProductDataContext = createContext( {
	product: defaultProductData,
	hasContext: false,
} );

export const useProductDataContext = () => useContext( ProductDataContext );

export const ProductDataContextProvider = ( {
	product = null,
	children,
	isLoading = false,
} ) => {
	const contextValue = {
		product: product || defaultProductData,
		hasContext: true,
	};

	return (
		<ProductDataContext.Provider value={ contextValue }>
			{ isLoading ? (
				<div className="is-loading">{ children }</div>
			) : (
				children
			) }
		</ProductDataContext.Provider>
	);
};

ProductDataContextProvider.propTypes = {
	children: PropTypes.node,
	product: PropTypes.object,
};
