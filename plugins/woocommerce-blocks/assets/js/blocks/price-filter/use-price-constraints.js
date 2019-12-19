/**
 * External dependencies
 */
import { usePrevious } from '@woocommerce/base-hooks';

/**
 * Internal dependencies
 */
import { ROUND_UP, ROUND_DOWN } from './constants';

/**
 * Return the price constraint.
 *
 * @param {number} price Price in minor unit, e.g. cents.
 * @param {number} minorUnit Price minor unit (number of digits after the decimal separator).
 * @param {ROUND_UP|ROUND_DOWN} direction Rounding flag whether we round up or down.
 */
export const usePriceConstraint = ( price, minorUnit, direction ) => {
	const step = 10 * 10 ** minorUnit;
	let currentConstraint;
	if ( direction === ROUND_UP ) {
		currentConstraint = isNaN( price )
			? null
			: Math.ceil( parseFloat( price, 10 ) / step ) * step;
	} else if ( direction === ROUND_DOWN ) {
		currentConstraint = isNaN( price )
			? null
			: Math.floor( parseFloat( price, 10 ) / step ) * step;
	}

	const previousConstraint = usePrevious( currentConstraint, ( val ) =>
		Number.isFinite( val )
	);
	return Number.isFinite( currentConstraint )
		? currentConstraint
		: previousConstraint;
};

export default ( { minPrice, maxPrice, minorUnit } ) => {
	return {
		minConstraint: usePriceConstraint( minPrice, minorUnit, ROUND_DOWN ),
		maxConstraint: usePriceConstraint( maxPrice, minorUnit, ROUND_UP ),
	};
};
