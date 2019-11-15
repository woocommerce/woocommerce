/**
 * Validate a min and max value for a range slider againt defined constraints (min, max, step).
 *
 * @param {Array} values Array containing min and max values.
 * @param {int} min Min allowed value for the sliders.
 * @param {int} max Max allowed value for the sliders.
 * @param {step} step Step value for the sliders.
 * @param {boolean} isMin Whether we're currently interacting with the min range slider or not, so we update the correct values.
 * @returns {Array} Validated and updated min/max values that fit within the range slider constraints.
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
