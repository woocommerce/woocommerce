/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';
import type { HTMLElementEvent } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { ActiveFilter, ProductFiltersStore } from '../../frontend';
import { formatPrice, getCurrency } from '../../utils';

export type ProductFilterPriceContext = {
	minRange: number;
	maxRange: number;
	hasFilterOptions: boolean;
	activeLabelTemplates: Record< string, string >;
};

function inRange( value: number, min: number, max: number ) {
	return value >= min && value <= max;
}

function activeFilterValue( min: null | number, max: null | number ) {
	if ( min === null && max === null ) return null;
	if ( max === null ) return `${ min }-`;
	if ( min === null ) return `-${ max }`;
	return `${ min }-${ max }`;
}

const { state, actions } = store( 'woocommerce/product-filter-price', {
	state: {
		get minPrice() {
			const context = getContext< ProductFilterPriceContext >();
			const productFiltersStore = store< ProductFiltersStore >(
				'woocommerce/product-filters'
			);
			return productFiltersStore.state.params?.min_price
				? parseInt( productFiltersStore.state.params.min_price, 10 )
				: context.minRange;
		},
		get maxPrice() {
			const context = getContext< ProductFilterPriceContext >();
			const productFiltersStore = store< ProductFiltersStore >(
				'woocommerce/product-filters'
			);
			return productFiltersStore.state.params?.max_price
				? parseInt( productFiltersStore.state.params.max_price, 10 )
				: context.maxRange;
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
		get hasSelectedFilters(): boolean {
			const context = getContext< ProductFilterPriceContext >();

			return (
				state.minPrice > context.minRange ||
				state.maxPrice < context.maxRange
			);
		},
	},
	actions: {
		getActiveLabel( min: null | number, max: null | number ) {
			const context = getContext< ProductFilterPriceContext >();
			if (
				min &&
				min > context.minRange &&
				max &&
				max < context.maxRange
			)
				return context.activeLabelTemplates.minAndMax
					.replace(
						'{{min}}',
						formatPrice( min, getCurrency( { minorUnit: 0 } ) )
					)
					.replace(
						'{{max}}',
						formatPrice( max, getCurrency( { minorUnit: 0 } ) )
					);

			if ( min && min > context.minRange ) {
				return context.activeLabelTemplates.minOnly.replace(
					'{{min}}',
					formatPrice( min, getCurrency( { minorUnit: 0 } ) )
				);
			}

			if ( max && max < context.maxRange ) {
				return context.activeLabelTemplates.maxOnly.replace(
					'{{max}}',
					formatPrice( max, getCurrency( { minorUnit: 0 } ) )
				);
			}

			return '';
		},
		setPrice: ( type: 'min' | 'max', value: number ) => {
			const context = getContext< ProductFilterPriceContext >();
			const productFiltersStore = store< ProductFiltersStore >(
				'woocommerce/product-filters'
			);

			const price: ActiveFilter[ 'price' ] = {
				min: state.minPrice,
				max: state.maxPrice,
			};

			if (
				type === 'min' &&
				value &&
				inRange( value, context.minRange, context.maxRange ) &&
				value < state.maxPrice
			) {
				price.min = value;
			}

			if (
				type === 'max' &&
				value &&
				inRange( value, context.minRange, context.maxRange ) &&
				value > state.minPrice
			) {
				price.max = value;
			}

			if ( price.min === context.minRange ) price.min = null;
			if ( price.max === context.maxRange ) price.max = null;

			productFiltersStore.actions.removeActiveFiltersByType( 'price' );

			productFiltersStore.actions.setActiveFilter( {
				type: 'price',
				value: activeFilterValue( price.min, price.max ),
				label: actions.getActiveLabel( price.min, price.max ),
				price,
			} );
		},
		setMinPrice: ( e: HTMLElementEvent< HTMLInputElement > ) => {
			const price = parseInt( e.target.value, 10 );
			actions.setPrice( 'min', price );
		},
		setMaxPrice: ( e: HTMLElementEvent< HTMLInputElement > ) => {
			const price = parseInt( e.target.value, 10 );
			actions.setPrice( 'max', price );
		},
		clearFilters: () => {
			const productFiltersStore = store< ProductFiltersStore >(
				'woocommerce/product-filters'
			);

			productFiltersStore.actions.removeActiveFiltersByType( 'price' );

			productFiltersStore.actions.navigate();
		},
	},
} );

export type ProductFilterPriceStore = {
	state: typeof state;
	actions: typeof actions;
};
