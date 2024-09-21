/**
 * External dependencies
 */
import { store, getContext, getElement } from '@woocommerce/interactivity';
import { formatPrice, getCurrency } from '@woocommerce/price-format';
import { HTMLElementEvent } from '@woocommerce/types';

type PriceFilterContext = {
	minPrice: number;
	maxPrice: number;
	minRange: number;
	maxRange: number;
};

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

store( 'woocommerce/product-filter-price-slider', {
	state: {
		rangeStyle: () => {
			const { minPrice, maxPrice, minRange, maxRange } =
				getContext< PriceFilterContext >();

			return `--low: ${
				( 100 * ( minPrice - minRange ) ) / ( maxRange - minRange )
			}%; --high: ${
				( 100 * ( maxPrice - minRange ) ) / ( maxRange - minRange )
			}%;`;
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
		selectInputContent: () => {
			const element = getElement();
			if ( element && element.ref ) {
				element.ref.select();
			}
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
