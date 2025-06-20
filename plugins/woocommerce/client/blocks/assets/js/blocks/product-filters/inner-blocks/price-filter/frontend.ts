/**
 * External dependencies
 */
import type { HTMLElementEvent } from '@woocommerce/types';
import * as iAPI from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import { ProductFiltersContext, ProductFiltersStore } from '../../frontend';
import { formatPrice, getCurrency } from '../../utils/price-currency';

const { store, getContext, getServerContext, getConfig } = iAPI;

function inRange( value: number, min: number, max: number ) {
	return value >= min && value <= max;
}

export type ProductFilterPriceContext = {
	minRange: number;
	maxRange: number;
};

const productFilterPriceStore = {
	state: {
		get minPrice() {
			const { activeFilters } = getContext< ProductFiltersContext >();
			const { minRange } = getServerContext
				? getServerContext< ProductFilterPriceContext >()
				: getContext< ProductFilterPriceContext >();
			const priceFilter = activeFilters.find(
				( filter ) => filter.type === 'price'
			);
			if ( priceFilter ) {
				const [ min ] = priceFilter.value.split( '|' );
				return min ? parseInt( min, 10 ) : minRange;
			}
			return minRange;
		},
		get maxPrice() {
			const { activeFilters } = getContext< ProductFiltersContext >();
			const { maxRange } = getServerContext
				? getServerContext< ProductFilterPriceContext >()
				: getContext< ProductFilterPriceContext >();
			const priceFilter = activeFilters.find(
				( filter ) => filter.type === 'price'
			);
			if ( priceFilter ) {
				const [ , max ] = priceFilter.value.split( '|' );
				return max ? parseInt( max, 10 ) : maxRange;
			}
			return maxRange;
		},
		get formattedMinPrice(): string {
			return formatPrice(
				state.minPrice,
				getCurrency( { minorUnit: 0 } )
			);
		},
		get formattedMaxPrice(): string {
			return formatPrice(
				state.maxPrice,
				getCurrency( { minorUnit: 0 } )
			);
		},
	},
	actions: {
		getActivePriceAndLabel( min: number, max: number ) {
			const { minRange, maxRange } = getServerContext
				? getServerContext< ProductFilterPriceContext >()
				: getContext< ProductFilterPriceContext >();
			const { activePriceLabelTemplates } = getConfig();
			if ( min && min > minRange && max && max < maxRange )
				return {
					activeValue: `${ min }|${ max }`,
					activeLabel: activePriceLabelTemplates.minAndMax
						.replace(
							'{{min}}',
							formatPrice( min, getCurrency( { minorUnit: 0 } ) )
						)
						.replace(
							'{{max}}',
							formatPrice( max, getCurrency( { minorUnit: 0 } ) )
						),
				};

			if ( min && min > minRange ) {
				return {
					activeValue: `${ min }|`,
					activeLabel: activePriceLabelTemplates.minOnly.replace(
						'{{min}}',
						formatPrice( min, getCurrency( { minorUnit: 0 } ) )
					),
				};
			}

			if ( max && max < maxRange ) {
				return {
					activeValue: `|${ max }`,
					activeLabel: activePriceLabelTemplates.maxOnly.replace(
						'{{max}}',
						formatPrice( max, getCurrency( { minorUnit: 0 } ) )
					),
				};
			}

			return {
				activeValue: '',
				activeLabel: '',
			};
		},
		setPrice: ( type: 'min' | 'max', value: number ) => {
			const context = getContext<
				ProductFilterPriceContext & ProductFiltersContext
			>();
			const { minRange, maxRange } = getServerContext
				? getServerContext< ProductFilterPriceContext >()
				: getContext< ProductFilterPriceContext >();
			const price: Record< string, number > = {
				min: state.minPrice,
				max: state.maxPrice,
			};

			if (
				type === 'min' &&
				value &&
				inRange( value, minRange, maxRange ) &&
				value < state.maxPrice
			) {
				price.min = value;
			}

			if (
				type === 'max' &&
				value &&
				inRange( value, minRange, maxRange ) &&
				value > state.minPrice
			) {
				price.max = value;
			}

			if ( price.min === minRange ) price.min = 0;
			if ( price.max === maxRange ) price.max = 0;

			context.activeFilters = context.activeFilters.filter(
				( item ) => item.type !== 'price'
			);
			const { activeValue, activeLabel } = actions.getActivePriceAndLabel(
				price.min,
				price.max
			);

			if ( activeValue ) {
				const newActivePriceFilter = {
					type: 'price',
					value: activeValue,
					activeLabel,
				};

				context.activeFilters.push( newActivePriceFilter );
			}
		},
		setMinPrice: ( e: HTMLElementEvent< HTMLInputElement > ) => {
			const price = parseInt( e.target.value, 10 );
			actions.setPrice( 'min', price );
		},
		setMaxPrice: ( e: HTMLElementEvent< HTMLInputElement > ) => {
			const price = parseInt( e.target.value, 10 );
			actions.setPrice( 'max', price );
		},
	},
};

export type ProductFilterPriceStore = typeof productFilterPriceStore;

const { state, actions } = store<
	ProductFiltersStore & ProductFilterPriceStore
>( 'woocommerce/product-filters', productFilterPriceStore );
