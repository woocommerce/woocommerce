/**
 * External dependencies
 */
import { sprintf } from '@wordpress/i18n';

/**
 * Validate a min and max value for a range slider againt defined constraints (min, max, step).
 *
 * @param {array} values Array containing min and max values.
 * @param {int} min Min allowed value for the sliders.
 * @param {int} max Max allowed value for the sliders.
 * @param {step} step Step value for the sliders.
 * @param {boolean} isMin Whether we're currently interacting with the min range slider or not, so we update the correct values.
 * @returns {array} Validated and updated min/max values that fit within the range slider constraints.
 */
export const constrainRangeSliderValues = ( values, min, max, step, isMin ) => {
	let minValue = parseInt( values[ 0 ], 10 ) || min;
	let maxValue = parseInt( values[ 1 ], 10 ) || step; // Max should be one step above min if invalid or 0.

	if ( min > minValue ) {
		minValue = min;
	}

	if ( max <= minValue ) {
		minValue = max - step;
	}

	if ( min >= maxValue ) {
		maxValue = min + step;
	}

	if ( max < maxValue ) {
		maxValue = max;
	}

	if ( ! isMin && minValue >= maxValue ) {
		minValue = maxValue - step;
	}

	if ( isMin && maxValue <= minValue ) {
		maxValue = minValue + step;
	}

	return [ minValue, maxValue ];
};

/**
 * Format a price with currency data.
 *
 * @param {number} value Number to format.
 * @param {string} priceFormat  Price format string.
 * @param {string} currencySymbol Curency symbol.
 */
export const formatCurrencyForInput = (
	value,
	priceFormat,
	currencySymbol
) => {
	if ( '' === value || undefined === value ) {
		return '';
	}
	const formattedNumber = parseInt( value, 10 );
	const formattedValue = sprintf(
		priceFormat,
		currencySymbol,
		formattedNumber
	);

	// This uses a textarea to magically decode HTML currency symbols.
	const txt = document.createElement( 'textarea' );
	txt.innerHTML = formattedValue;
	return txt.value;
};
