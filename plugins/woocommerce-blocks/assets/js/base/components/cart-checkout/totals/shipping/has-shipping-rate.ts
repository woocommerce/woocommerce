/**
 * External dependencies
 */
import type { CartResponseShippingRate } from '@woocommerce/types';

/**
 * Searches an array of packages/rates to see if there are actually any rates
 * available.
 *
 * @param {Array} shippingRatePackages An array of packages and rates.
 * @return {boolean} True if a rate exists.
 */
const hasShippingRate = (
	shippingRatePackages: CartResponseShippingRate[]
): boolean => {
	return shippingRatePackages.some(
		( shippingRatePackage ) => shippingRatePackage.shipping_rates.length
	);
};

export default hasShippingRate;
