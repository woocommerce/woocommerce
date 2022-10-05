/**
 * External dependencies
 */
import type { CartShippingPackageShippingRate } from '@woocommerce/type-defs/cart';

/**
 * Returns the cheapest rate that isn't a local pickup.
 *
 * @param {Array|undefined} shippingRates Array of shipping Rate.
 *
 * @return {Object|undefined} cheapest rate.
 */
export function getShippingStartingPrice(
	shippingRates: CartShippingPackageShippingRate[]
): CartShippingPackageShippingRate | undefined {
	if ( shippingRates ) {
		return shippingRates.reduce(
			(
				lowestRate: CartShippingPackageShippingRate | undefined,
				currentRate: CartShippingPackageShippingRate
			) => {
				if ( currentRate.method_id === 'local_pickup' ) {
					return lowestRate;
				}
				if (
					lowestRate === undefined ||
					currentRate.price < lowestRate.price
				) {
					return currentRate;
				}
				return lowestRate;
			},
			undefined
		);
	}
}

/**
 * Returns the cheapest rate that is a local pickup.
 *
 * @param {Array|undefined} shippingRates Array of shipping Rate.
 *
 * @return {Object|undefined} cheapest rate.
 */
export function getLocalPickupStartingPrice(
	shippingRates: CartShippingPackageShippingRate[]
): CartShippingPackageShippingRate | undefined {
	if ( shippingRates ) {
		return shippingRates.reduce(
			(
				lowestRate: CartShippingPackageShippingRate | undefined,
				currentRate: CartShippingPackageShippingRate
			) => {
				if ( currentRate.method_id !== 'local_pickup' ) {
					return lowestRate;
				}
				if (
					lowestRate === undefined ||
					currentRate.price < lowestRate.price
				) {
					return currentRate;
				}
				return lowestRate;
			},
			undefined
		);
	}
}
