/**
 * Constructs a shipping method based on the given `methodId` and `cost`.
 *
 * `methodId` should be one of the following:
 * - `free_shipping`
 * - `flat_rate`
 * - `local_pickup`
 *
 * @param {string}  methodId
 * @param {number=} cost
 * @return {ShippingMethodExample} Shipping method object that can serve as a request payload for adding a shipping method to a shipping zone.
 */
interface ShippingMethodExample {
	method_id: string;
	settings?: { cost: number };
}

export const getShippingMethodExample = ( methodId: string, cost?: number ) => {
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
