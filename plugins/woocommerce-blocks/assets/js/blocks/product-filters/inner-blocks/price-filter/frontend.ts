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
		const validatedPrices: Record< string, string > = {};
		const params = { ...parentContext.params };

		if ( 'min_price' in price ) {
			if (
				Number( price.min_price ) >= context.minRange &&
				Number( price.minRange ) <= context.maxRange
			) {
				validatedPrices.min_price = price.min_price;
			} else {
				delete params.min_price;
			}
		}

		if ( 'max_price' in price ) {
			if (
				Number( price.max_price ) >= context.minRange &&
				Number( price.maxRange ) <= context.maxRange
			) {
				validatedPrices.max_price = price.max_price;
			} else {
				delete params.max_price;
			}
		}

		parentContext.params = {
			...params,
			...validatedPrices,
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
