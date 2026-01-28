/**
 * Constructs a shipping method based on the given `methodId` and `cost`.
 *
 * `methodId` should be one of the following:
 * - `free_shipping`
 * - `flat_rate`
 * - `local_pickup`
 *
 * @param methodId
 * @param cost
 * @return shipping method object that can serve as a request payload for adding a shipping method to a shipping zone.
 */

export type ShippingMethodId = 'free_shipping' | 'flat_rate' | 'local_pickup';

export interface ShippingMethodSettings {
	cost: number;
}

export interface ShippingMethod {
	method_id: ShippingMethodId;
	settings?: ShippingMethodSettings;
}

export const getShippingMethodExample = (
	methodId: ShippingMethodId,
	cost?: number
): ShippingMethod => {
	const shippingMethodExample: ShippingMethod = {
		method_id: methodId,
	};

	if ( cost !== undefined ) {
		shippingMethodExample.settings = {
			cost,
		};
	}

	return shippingMethodExample;
};
