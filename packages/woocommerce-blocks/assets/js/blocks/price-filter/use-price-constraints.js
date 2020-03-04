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
 * @param {ROUND_UP|ROUND_DOWN} direction Rounding flag whether we round up or down.
 */
export const usePriceConstraint = ( price, direction ) => {
	let currentConstraint;
	if ( direction === ROUND_UP ) {
		currentConstraint = isNaN( price )
			? null
			: Math.ceil( parseFloat( price, 10 ) / 10 ) * 10;
	} else if ( direction === ROUND_DOWN ) {
		currentConstraint = isNaN( price )
			? null
			: Math.floor( parseFloat( price, 10 ) / 10 ) * 10;
	}

	const previousConstraint = usePrevious( currentConstraint, ( val ) =>
		Number.isFinite( val )
	);
	return Number.isFinite( currentConstraint )
		? currentConstraint
		: previousConstraint;
};

export default ( { minPrice, maxPrice } ) => {
	return {
		minConstraint: usePriceConstraint( minPrice, ROUND_DOWN ),
		maxConstraint: usePriceConstraint( maxPrice, ROUND_UP ),
	};
};
