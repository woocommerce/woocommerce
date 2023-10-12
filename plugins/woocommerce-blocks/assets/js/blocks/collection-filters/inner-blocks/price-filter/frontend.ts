/**
 * External dependencies
 */
import { store, navigate } from '@woocommerce/interactivity';
import { formatPrice, getCurrency } from '@woocommerce/price-format';

/**
 * Internal dependencies
 */
import { ActionProps, StateProps } from './types';

const getHrefWithFilters = ( { state }: StateProps ) => {
	const { minPrice, maxPrice } = state.filters;
	const url = new URL( window.location.href );
	const { searchParams } = url;

	if ( minPrice > 0 ) {
		searchParams.set( 'min_price', minPrice.toString() );
	} else {
		searchParams.delete( 'min_price' );
	}

	if ( maxPrice < state.filters.maxRange ) {
		searchParams.set( 'max_price', maxPrice.toString() );
	} else {
		searchParams.delete( 'max_price' );
	}

	searchParams.forEach( ( _, key ) => {
		if ( /query-[0-9]+-page/.test( key ) ) searchParams.delete( key );
	} );

	return url.href;
};

store( {
	state: {
		filters: {
			rangeStyle: ( { state }: StateProps ) => {
				const { minPrice, maxPrice, maxRange } = state.filters;
				return [
					`--low: ${ ( 100 * minPrice ) / maxRange }%`,
					`--high: ${ ( 100 * maxPrice ) / maxRange }%`,
				].join( ';' );
			},
			formattedMinPrice: ( { state }: StateProps ) => {
				const { minPrice } = state.filters;
				return formatPrice( minPrice, getCurrency( { minorUnit: 0 } ) );
			},
			formattedMaxPrice: ( { state }: StateProps ) => {
				const { maxPrice } = state.filters;
				return formatPrice( maxPrice, getCurrency( { minorUnit: 0 } ) );
			},
		},
	},
	actions: {
		filters: {
			setMinPrice: ( { state, event }: ActionProps ) => {
				const value = parseFloat( event.target.value );
				state.filters.minPrice = Math.min(
					Number.isNaN( value ) ? state.filters.minRange : value,
					state.filters.maxRange - 1
				);
				state.filters.maxPrice = Math.max(
					state.filters.maxPrice,
					state.filters.minPrice + 1
				);
			},
			setMaxPrice: ( { state, event }: ActionProps ) => {
				const value = parseFloat( event.target.value );
				state.filters.maxPrice = Math.max(
					Number.isNaN( value ) ? state.filters.maxRange : value,
					state.filters.minRange + 1
				);
				state.filters.minPrice = Math.min(
					state.filters.minPrice,
					state.filters.maxPrice - 1
				);
			},
			updateProducts: ( { state }: ActionProps ) => {
				navigate( getHrefWithFilters( { state } ) );
			},
			reset: ( { state }: ActionProps ) => {
				state.filters.minPrice = 0;
				state.filters.maxPrice = state.filters.maxRange;
				navigate( getHrefWithFilters( { state } ) );
			},
		},
	},
} );
