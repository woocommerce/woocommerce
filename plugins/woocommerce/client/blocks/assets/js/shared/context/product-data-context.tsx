/**
 * External dependencies
 */
import { ProductEntityResponse } from '@woocommerce/entities';
import { ProductResponseItem } from '@woocommerce/types';
import { createContext, useContext } from '@wordpress/element';

/**
 * Default product shape matching API response.
 */
const defaultProductData: ProductResponseItem = {
	id: 0,
	name: '',
	parent: 0,
	type: 'simple',
	variation: '',
	permalink: '',
	sku: '',
	slug: '',
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
	stock_availability: {
		text: '',
		class: '',
	},
	sold_individually: false,
	add_to_cart: {
		text: 'Add to cart',
		single_text: 'Add to cart',
		description: 'Add to cart',
		url: '',
		minimum: 1,
		maximum: 99,
		multiple_of: 1,
	},
	grouped_products: [],
};

/**
 * This context is used to pass product data down to all children blocks in a given tree.
 *
 * @member {Object} ProductDataContext A react context object
 */
const ProductDataContext = createContext< {
	product: ProductResponseItem | ProductEntityResponse;
	hasContext: boolean;
	isLoading: boolean;
} >( {
	product: defaultProductData,
	hasContext: false,
	isLoading: false,
} );

type UseProductDataContextProps = {
	isAdmin?: boolean | undefined;
	product?: ProductResponseItem | ProductEntityResponse | undefined;
	isResolving?: boolean | undefined;
};

/**
 * Hook that provides product data context for WooCommerce blocks.
 *
 * This hook serves as a unified interface for accessing product data across different environments for WooCommerce blocks that have the JS version for the frontend.
 * - Frontend: Returns the React context data from ProductDataContext
 * - Admin/Editor: Uses the new entity-based data fetching system via WordPress Core Data API
 *
 * The dual behavior ensures blocks work consistently in both frontend display and admin editing
 * contexts while leveraging the most appropriate data source for each environment.
 *
 * @param props             Configuration object for the hook
 * @param props.isAdmin     Whether the hook is being used in the admin/editor context
 * @param props.product     Product data to use directly (admin context)
 * @param props.isResolving Whether product data is currently being fetched (admin context)
 * @return Object containing product data and loading state
 */
export const useProductDataContext = (
	props: UseProductDataContextProps = {
		isAdmin: false,
	}
) => {
	const context = useContext( ProductDataContext );
	const { isAdmin, product, isResolving } = props;

	if ( ! isAdmin ) {
		return context;
	}

	return {
		product,
		isLoading: isResolving,
	};
};

interface ProductDataContextProviderProps {
	product: ProductResponseItem | ProductEntityResponse | null;
	children: JSX.Element | JSX.Element[];
	isLoading: boolean;
}

/**
 * This context is used to pass product data down to all children blocks in a given tree.
 *
 * @param {Object}   object           A react context object
 * @param {any|null} object.product   The product data to be passed down
 * @param {Object}   object.children  The product data to be passed down
 * @param {boolean}  object.isLoading The product data to be passed down
 */
export const ProductDataContextProvider = ( {
	product = null,
	children,
	isLoading,
}: ProductDataContextProviderProps ) => {
	const contextValue = {
		product: product || defaultProductData,
		isLoading,
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
