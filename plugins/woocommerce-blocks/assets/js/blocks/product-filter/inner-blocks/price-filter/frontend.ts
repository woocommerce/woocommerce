/**
 * External dependencies
 */
import { store, getContext } from '@woocommerce/interactivity';
import { formatPrice, getCurrency } from '@woocommerce/price-format';
import { HTMLElementEvent } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { navigate } from '../../frontend';
import type { PriceFilterContext, PriceFilterStore } from './types';

const getUrl = ( context: PriceFilterContext ) => {
	const { minPrice, maxPrice, minRange, maxRange } = context;
	const url = new URL( window.location.href );
	const { searchParams } = url;

	if ( minPrice > minRange ) {
		searchParams.set( 'min_price', minPrice.toString() );
	} else {
		searchParams.delete( 'min_price' );
	}

	if ( maxPrice < maxRange && maxPrice > minRange ) {
		searchParams.set( 'max_price', maxPrice.toString() );
	} else {
		searchParams.delete( 'max_price' );
	}

	searchParams.forEach( ( _, key ) => {
		if ( /query-[0-9]+-page/.test( key ) ) searchParams.delete( key );
	} );

	return url.href;
};

store< PriceFilterStore >( 'woocommerce/product-filter-price', {
	state: {
		rangeStyle: () => {
			const { minPrice, maxPrice, minRange, maxRange } =
				getContext< PriceFilterContext >();

			return [
				`--low: ${
					( 100 * ( minPrice - minRange ) ) / ( maxRange - minRange )
				}%`,
				`--high: ${
					( 100 * ( maxPrice - minRange ) ) / ( maxRange - minRange )
				}%`,
			].join( ';' );
		},
		formattedMinPrice: () => {
			const { minPrice } = getContext< PriceFilterContext >();
			return formatPrice( minPrice, getCurrency( { minorUnit: 0 } ) );
		},
		formattedMaxPrice: () => {
			const { maxPrice } = getContext< PriceFilterContext >();
			return formatPrice( maxPrice, getCurrency( { minorUnit: 0 } ) );
		},
	},
	actions: {
		updateProducts: ( event: HTMLElementEvent< HTMLInputElement > ) => {
			const context = getContext< PriceFilterContext >();
			const { minRange, minPrice, maxPrice, maxRange } = context;
			const type = event.target.name;
			const value = parseFloat( event.target.value );

			const currentMinPrice =
				type === 'min'
					? Math.min(
							Number.isNaN( value ) ? minRange : value,
							maxRange - 1
					  )
					: minPrice;
			const currentMaxPrice =
				type === 'max'
					? Math.max(
							Number.isNaN( value ) ? maxRange : value,
							minRange + 1
					  )
					: maxPrice;

			context.minPrice = currentMinPrice;
			context.maxPrice = currentMaxPrice;

			navigate(
				getUrl( {
					minRange,
					maxRange,
					minPrice: currentMinPrice,
					maxPrice: currentMaxPrice,
				} )
			);
		},
		reset: () => {
			navigate(
				getUrl( {
					minRange: 0,
					maxRange: 0,
					minPrice: 0,
					maxPrice: 0,
				} )
			);
		},
	},
} );
