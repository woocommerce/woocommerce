/**
 * External dependencies
 */
import { store, navigate } from '@woocommerce/interactivity';
import { formatPrice, getCurrency } from '@woocommerce/price-format';
import { HTMLElementEvent } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { PriceFilterState } from './types';

const getHrefWithFilters = ( state: PriceFilterState ) => {
	const { minPrice = 0, maxPrice = 0, maxRange = 0 } = state;
	const url = new URL( window.location.href );
	const { searchParams } = url;

	if ( minPrice > 0 ) {
		searchParams.set( 'min_price', minPrice.toString() );
	} else {
		searchParams.delete( 'min_price' );
	}

	if ( maxPrice < maxRange ) {
		searchParams.set( 'max_price', maxPrice.toString() );
	} else {
		searchParams.delete( 'max_price' );
	}

	searchParams.forEach( ( _, key ) => {
		if ( /query-[0-9]+-page/.test( key ) ) searchParams.delete( key );
	} );

	return url.href;
};

interface PriceFilterStore {
	state: PriceFilterState;
	actions: {
		setMinPrice: ( event: HTMLElementEvent< HTMLInputElement > ) => void;
		setMaxPrice: ( event: HTMLElementEvent< HTMLInputElement > ) => void;
		updateProducts: () => void;
		reset: () => void;
	};
}

const { state } = store< PriceFilterStore >(
	'woocommerce/collection-price-filter',
	{
		state: {
			get rangeStyle(): string {
				const {
					minPrice = 0,
					maxPrice = 0,
					minRange = 0,
					maxRange = 0,
				} = state;
				return [
					`--low: ${
						( 100 * ( minPrice - minRange ) ) /
						( maxRange - minRange )
					}%`,
					`--high: ${
						( 100 * ( maxPrice - minRange ) ) /
						( maxRange - minRange )
					}%`,
				].join( ';' );
			},
			get formattedMinPrice(): string {
				const { minPrice = 0 } = state;
				return formatPrice( minPrice, getCurrency( { minorUnit: 0 } ) );
			},
			get formattedMaxPrice(): string {
				const { maxPrice = 0 } = state;
				return formatPrice( maxPrice, getCurrency( { minorUnit: 0 } ) );
			},
		},
		actions: {
			setMinPrice: ( event: HTMLElementEvent< HTMLInputElement > ) => {
				const { minRange = 0, maxPrice = 0, maxRange = 0 } = state;
				const value = parseFloat( event.target.value );
				state.minPrice = Math.min(
					Number.isNaN( value ) ? minRange : value,
					maxRange - 1
				);
				state.maxPrice = Math.max( maxPrice, state.minPrice + 1 );
			},
			setMaxPrice: ( event: HTMLElementEvent< HTMLInputElement > ) => {
				const {
					minRange = 0,
					minPrice = 0,
					maxPrice = 0,
					maxRange = 0,
				} = state;
				const value = parseFloat( event.target.value );
				state.maxPrice = Math.max(
					Number.isNaN( value ) ? maxRange : value,
					minRange + 1
				);
				state.minPrice = Math.min( minPrice, maxPrice - 1 );
			},
			updateProducts: () => {
				navigate( getHrefWithFilters( state ) );
			},
			reset: () => {
				const { maxRange = 0 } = state;
				state.minPrice = 0;
				state.maxPrice = maxRange;
				navigate( getHrefWithFilters( state ) );
			},
		},
	}
);
