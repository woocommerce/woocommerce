/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import type { CartResponseShippingRate } from '@woocommerce/type-defs/cart-response';
import { hasCollectableRate } from '@woocommerce/base-utils';

/**
 * Searches an array of packages/rates to see if there are actually any rates
 * available.
 *
 * @param {Array} shippingRatePackages An array of packages and rates.
 * @return {boolean} True if a rate exists.
 */
export const hasShippingRate = (
	shippingRatePackages: CartResponseShippingRate[]
): boolean => {
	return shippingRatePackages.some(
		( shippingRatePackage ) => shippingRatePackage.shipping_rates.length
	);
};

/**
 * Calculates the total shipping value based on store settings.
 */
export const getTotalShippingValue = ( values: {
	total_shipping: string;
	total_shipping_tax: string;
} ): number => {
	return getSetting( 'displayCartPricesIncludingTax', false )
		? parseInt( values.total_shipping, 10 ) +
				parseInt( values.total_shipping_tax, 10 )
		: parseInt( values.total_shipping, 10 );
};

/**
 * Checks if no shipping methods are available or if all available shipping methods are local pickup
 * only.
 */
export const areShippingMethodsMissing = (
	hasRates: boolean,
	prefersCollection: boolean | undefined,
	shippingRates: CartResponseShippingRate[]
) => {
	if ( ! hasRates ) {
		// No shipping methods available
		return true;
	}

	// We check for the availability of shipping options if the shopper selected "Shipping"
	if ( ! prefersCollection ) {
		return shippingRates.some(
			( shippingRatePackage ) =>
				! shippingRatePackage.shipping_rates.some(
					( shippingRate ) =>
						! hasCollectableRate( shippingRate.method_id )
				)
		);
	}

	return false;
};
