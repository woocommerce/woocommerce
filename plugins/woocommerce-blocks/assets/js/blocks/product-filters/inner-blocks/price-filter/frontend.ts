/**
 * External dependencies
 */
import { store, getContext, getElement } from '@woocommerce/interactivity';
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
		Object.keys( price ).forEach( ( key ) => {
			if ( key !== 'min_price' && key !== 'max_price' ) {
				delete price[ key ];
				return;
			}
			if ( ! price[ key ] ) {
				delete price[ key ];
			}
		} );

		const params = parentContext.params;

		if ( 'min_price' in price ) {
			if (
				Number( price.min_price ) >= context.maxRange ||
				Number( price.min_price ) <= context.minRange
			) {
				delete params.min_price;
				delete price.min_price;
			}
		}

		if ( 'max_price' in price ) {
			if (
				Number( price.max_price ) <= context.minRange ||
				Number( price.max_price ) >= context.maxRange
			) {
				delete params.max_price;
				delete price.max_price;
			}
		}

		parentContext.params = {
			...params,
			...price,
		};
	},
	100
);

store( 'woocommerce/product-filter-price', {
	actions: {
		setPrices: () => {
			const { ref } = getElement();
			const targetMinPriceAttribute =
				ref.getAttribute( 'data-target-min-price' ) ?? 'data-min-price';
			const targetMaxPriceAttribute =
				ref.getAttribute( 'data-target-max-price' ) ?? 'data-max-price';
			const prices: Record< string, string > = {};

			const minPrice = ref.getAttribute( targetMinPriceAttribute );
			if ( minPrice ) {
				prices.min_price = minPrice;
			}

			const maxPrice = ref.getAttribute( targetMaxPriceAttribute );
			if ( maxPrice ) {
				prices.max_price = maxPrice;
			}

			const productFiltersContext = getContext< ProductFiltersContext >(
				'woocommerce/product-filters'
			);
			const context = getContext< PriceFilterContext >();
			debounceUpdate( context, productFiltersContext, prices );
		},
		clearFilters: () => {
			const productFiltersContext = getContext< ProductFiltersContext >(
				'woocommerce/product-filters'
			);
			const updatedParams = productFiltersContext.params;

			delete updatedParams.min_price;
			delete updatedParams.max_price;

			productFiltersContext.params = updatedParams;
		},
	},
} );
