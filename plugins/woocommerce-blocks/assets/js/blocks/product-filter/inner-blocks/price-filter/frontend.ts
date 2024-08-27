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

const constrainRangeSliderValues = (
	/**
	 * Tuple containing min and max values.
	 */
	values: [ number, number ],
	/**
	 * Min allowed value for the sliders.
	 */
	min?: number | null,
	/**
	 * Max allowed value for the sliders.
	 */
	max?: number | null,
	/**
	 * Step value for the sliders.
	 */
	step = 1,
	/**
	 * Whether we're currently interacting with the min range slider or not, so we update the correct values.
	 */
	isMin = false
): { minPrice: number; maxPrice: number } => {
	let [ minValue, maxValue ] = values;

	const isFinite = ( n: number | null | undefined ): n is number =>
		Number.isFinite( n );

	if ( ! isFinite( minValue ) ) {
		minValue = min || 0;
	}

	if ( ! isFinite( maxValue ) ) {
		maxValue = max || step;
	}

	if ( isFinite( min ) && min > minValue ) {
		minValue = min;
	}

	if ( isFinite( max ) && max <= minValue ) {
		minValue = max - step;
	}

	if ( isFinite( min ) && min >= maxValue ) {
		maxValue = min + step;
	}

	if ( isFinite( max ) && max < maxValue ) {
		maxValue = max;
	}

	if ( ! isMin && minValue >= maxValue ) {
		minValue = maxValue - step;
	}

	if ( isMin && maxValue <= minValue ) {
		maxValue = minValue + step;
	}

	return { minPrice: minValue, maxPrice: maxValue };
};

const getRangeStyle = (
	minPrice: number,
	maxPrice: number,
	minRange: number,
	maxRange: number
) => {
	return `--low: ${
		( 100 * ( minPrice - minRange ) ) / ( maxRange - minRange )
	}%; --high: ${
		( 100 * ( maxPrice - minRange ) ) / ( maxRange - minRange )
	}%;`;
};

store< PriceFilterStore >( 'woocommerce/product-filter-price', {
	state: {
		rangeStyle: () => {
			const { minPrice, maxPrice, minRange, maxRange } =
				getContext< PriceFilterContext >();

			return getRangeStyle( minPrice, maxPrice, minRange, maxRange );
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
		updateRange: ( event: HTMLElementEvent< HTMLInputElement > ) => {
			const context = getContext< PriceFilterContext >();
			const { minPrice, maxPrice, minRange, maxRange } = context;
			const isMin = event.target.classList.contains( 'min' );
			const targetValue = Number( event.target.value );
			const stepValue = 1;
			const currentValues: [ number, number ] = isMin
				? [
						Math.round( targetValue / stepValue ) * stepValue,
						maxPrice,
				  ]
				: [
						minPrice,
						Math.round( targetValue / stepValue ) * stepValue,
				  ];
			const values = constrainRangeSliderValues(
				currentValues,
				minRange,
				maxRange,
				stepValue,
				isMin
			);

			if ( targetValue >= maxPrice ) {
				context.maxPrice = minPrice + 1;
			} else if ( targetValue <= minPrice ) {
				context.minPrice = maxPrice - 1;
			}

			if ( isMin ) {
				context.minPrice = values.minPrice;
			} else {
				context.maxPrice = values.maxPrice;
			}
		},
	},
} );
