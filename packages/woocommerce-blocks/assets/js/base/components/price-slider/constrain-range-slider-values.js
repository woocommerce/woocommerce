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
export const constrainRangeSliderValues = (
	values,
	min,
	max,
	step = 1,
	isMin = false
) => {
	let minValue = parseInt( values[ 0 ], 10 );
	let maxValue = parseInt( values[ 1 ], 10 );

	if ( ! Number.isFinite( minValue ) ) {
		minValue = min || 0;
	}

	if ( ! Number.isFinite( maxValue ) ) {
		maxValue = max || step;
	}

	if ( Number.isFinite( min ) && min > minValue ) {
		minValue = min;
	}

	if ( Number.isFinite( max ) && max <= minValue ) {
		minValue = max - step;
	}

	if ( Number.isFinite( min ) && min >= maxValue ) {
		maxValue = min + step;
	}

	if ( Number.isFinite( max ) && max < maxValue ) {
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
