/**
 * External dependencies
 */
import { store, getContext, getElement } from '@woocommerce/interactivity';
import { formatPrice, getCurrency } from '@woocommerce/price-format';
import { HTMLElementEvent } from '@woocommerce/types';
import { debounce } from '@woocommerce/base-utils';

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

const debounceUpdate = debounce( ( context, element, event ) => {
	const { decimalSeparator } = getCurrency();
	const { minRange, minPrice, maxPrice, maxRange } = context;
	const type = event.target.name;
	const value = parseInt(
		event.target.value
			.replace( new RegExp( `[^0-9\\${ decimalSeparator }]+`, 'g' ), '' )
			.replace( new RegExp( `\\${ decimalSeparator }`, 'g' ), '.' ),
		10
	);

	const currentMinPrice =
		type === 'min'
			? Math.min( Number.isNaN( value ) ? minRange : value, maxPrice )
			: minPrice;

	const currentMaxPrice =
		type === 'max'
			? Math.max( Number.isNaN( value ) ? maxRange : value, minPrice )
			: maxPrice;

	if ( type === 'min' ) {
		element.ref.value = currentMinPrice;
	} else if ( type === 'max' ) {
		element.ref.value = currentMaxPrice;
	}

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
}, 1000 );

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

			// In some occasions the input element is updated with the incorrect value.
			// By using the element that triggered the event, we can ensure the correct value is used for the input.
			const element = getElement();

			debounceUpdate( context, element, event );
		},
		selectInputContent: () => {
			const element = getElement();
			if ( element && element.ref ) {
				element.ref.select();
			}
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
