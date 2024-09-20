/**
 * External dependencies
 */
import { store, getContext } from '@woocommerce/interactivity';

/**
 * Internal dependencies
 */
import { ProductFiltersContext } from '../../frontend';

store( 'woocommerce/product-filter-price', {
	actions: {
		setMinPrice: ( price: number ) => {
			const productFiltersContext = getContext< ProductFiltersContext >(
				'woocommerce/product-filters'
			);
			productFiltersContext.params.min_price = price.toString();
		},
		setMaxPrice: ( price: number ) => {
			const productFiltersContext = getContext< ProductFiltersContext >(
				'woocommerce/product-filters'
			);
			productFiltersContext.params.max_price = price.toString();
		},
		setPriceRange: ( minPrice: number, maxPrice: number ) => {
			const productFiltersContext = getContext< ProductFiltersContext >(
				'woocommerce/product-filters'
			);
			productFiltersContext.params = {
				...productFiltersContext.params,
				min_price: minPrice.toString(),
				max_price: maxPrice.toString(),
			};
		},
	},
} );
