/**
 * Default shipping zone object.
 *
 * For more details on shipping zone properties, see:
 *
 * https://woocommerce.github.io/woocommerce-rest-api-docs/#shipping-zone-properties
 *
 */

export interface ShippingZone {
	name: string;
}

const shippingZone: ShippingZone = {
	name: 'US Domestic',
};

/**
 * Constructs a default shipping zone object.
 *
 */
export const getShippingZoneExample = (): ShippingZone => {
	return shippingZone;
};
