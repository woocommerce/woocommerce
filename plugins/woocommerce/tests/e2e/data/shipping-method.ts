/**
 * Constructs a shipping method based on the given `methodId` and `cost`.
 *
 * @param {ShippingMethodId} methodId
 * @param {number=}          cost
 * @return {ShippingMethodExample} Shipping method object that can serve as a request payload for adding a shipping method to a shipping zone.
 */
type ShippingMethodId = 'free_shipping' | 'flat_rate' | 'local_pickup';

interface ShippingMethodExample {
	method_id: ShippingMethodId;
	settings?: { cost: number };
}

export const getShippingMethodExample = (
	methodId: ShippingMethodId,
	cost?: number
) => {
	const shippingMethodExample: ShippingMethodExample = {
		method_id: methodId,
	};

	if ( cost !== undefined ) {
		shippingMethodExample.settings = {
			cost,
		};
	}

	return shippingMethodExample;
};
