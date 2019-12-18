/**
 * External dependencies
 */
import { usePrevious } from '@woocommerce/base-hooks';

/**
 * Return the price constraint.
 *
 * @param {number} price Price in minor unit, e.g. cents.
 * @param {number} minorUnit Price minor unit (number of digits after the decimal separator).
 */
export const usePriceConstraint = ( price, minorUnit ) => {
	const step = 10 * 10 ** minorUnit;
	const currentConstraint = isNaN( price )
		? null
		: Math.floor( parseInt( price, 10 ) / step ) * step;
	const previousConstraint = usePrevious( currentConstraint, ( val ) =>
		Number.isFinite( val )
	);
	return Number.isFinite( currentConstraint )
		? currentConstraint
		: previousConstraint;
};

export default ( { minPrice, maxPrice, minorUnit } ) => {
	return {
		minConstraint: usePriceConstraint( minPrice, minorUnit ),
		maxConstraint: usePriceConstraint( maxPrice, minorUnit ),
	};
};
