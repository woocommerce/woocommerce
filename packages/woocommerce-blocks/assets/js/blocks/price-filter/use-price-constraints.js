/**
 * External dependencies
 */
import { usePrevious } from '@woocommerce/base-hooks';

export const usePriceConstraint = ( price ) => {
	const currentConstraint = isNaN( price )
		? null
		: Math.floor( parseInt( price, 10 ) / 10 ) * 10;
	const previousConstraint = usePrevious( currentConstraint, ( val ) =>
		Number.isFinite( val )
	);
	return Number.isFinite( currentConstraint )
		? currentConstraint
		: previousConstraint;
};

export default ( { minPrice, maxPrice } ) => {
	return {
		minConstraint: usePriceConstraint( minPrice ),
		maxConstraint: usePriceConstraint( maxPrice ),
	};
};
