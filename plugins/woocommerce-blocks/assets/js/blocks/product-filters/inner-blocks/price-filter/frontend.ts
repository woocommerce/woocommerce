/**
 * External dependencies
 */
import { store, getContext } from '@woocommerce/interactivity';
import { debounce } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import { ProductFiltersContext } from '../../frontend';

type PriceFilterContext = {
	minPrice: number;
	maxPrice: number;
	minRange: number;
	maxRange: number;
};

const debounceUpdate = debounce(
	(
		context: PriceFilterContext,
		parentContext: ProductFiltersContext,
		price: Record< string, string >
	) => {
		const params = parentContext.params;
		if (
			'min_price' in price &&
			price.min_price.toString() === context.minRange.toString()
		) {
			delete params.min_price;
			delete price.min_price;
		}
		if (
			'max_price' in price &&
			price.max_price.toString() === context.maxRange.toString()
		) {
			delete params.max_price;
			delete price.max_price;
		}
		console.log( params, price );
		parentContext.params = {
			...params,
			...price,
		};
	},
	300
);

store( 'woocommerce/product-filter-price', {
	actions: {
		setMinPrice: ( price: number ) => {
			const context = getContext< PriceFilterContext >();
			const productFiltersContext = getContext< ProductFiltersContext >(
				'woocommerce/product-filters'
			);
			debounceUpdate( context, productFiltersContext, {
				min_price: price.toString(),
			} );
		},
		setMaxPrice: ( price: number ) => {
			const context = getContext< PriceFilterContext >();
			const productFiltersContext = getContext< ProductFiltersContext >(
				'woocommerce/product-filters'
			);
			debounceUpdate( context, productFiltersContext, {
				max_price: price.toString(),
			} );
		},
		setPriceRange: ( minPrice: number, maxPrice: number ) => {
			const context = getContext< PriceFilterContext >();
			const productFiltersContext = getContext< ProductFiltersContext >(
				'woocommerce/product-filters'
			);
			debounceUpdate( context, productFiltersContext, {
				min_price: minPrice.toString(),
				max_price: maxPrice.toString(),
			} );
		},
	},
} );
