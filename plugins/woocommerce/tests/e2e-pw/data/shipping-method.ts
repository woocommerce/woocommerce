/**
 * Constructs a shipping method based on the given `methodId` and `cost`.
 *
 * `methodId` should be one of the following:
 * - `free_shipping`
 * - `flat_rate`
 * - `local_pickup`
 *
 * @param {number} methodId
 * @param {number} cost
 * @return {Object} shipping method object that can serve as a request payload for adding a shipping method to a shipping zone.
 */
export const getShippingMethodExample = ( methodId: string, cost?: number ) => {
	const shippingMethodExample: any = {
		method_id: methodId,
	};

	if ( cost !== undefined ) {
		shippingMethodExample.settings = {
			cost,
		};
	}

	return shippingMethodExample;
};
