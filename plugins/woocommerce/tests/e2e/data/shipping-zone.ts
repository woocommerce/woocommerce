/**
 * Default shipping zone object.
 *
 * For more details on shipping zone properties, see:
 *
 * https://developer.woocommerce.com/docs/apis/rest-api/v3/shipping-zones/#shipping-zone-properties
 *
 */
const shippingZone = {
	name: 'US Domestic',
};

/**
 * Constructs a default shipping zone object.
 *
 */
export const getShippingZoneExample = () => {
	return shippingZone;
};
