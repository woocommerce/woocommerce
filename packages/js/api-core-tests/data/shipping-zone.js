/**
 * Default shipping zone object.
 *
 * For more details on shipping zone properties, see:
 *
 * https://woocommerce.github.io/woocommerce-rest-api-docs/#shipping-zone-properties
 *
 */
const shippingZone = {
	name: 'US Domestic',
};

/**
 * Constructs a default shipping zone object.
 *
 * @return {Object} default shipping zone
 */
const getShippingZoneExample = () => {
	return shippingZone;
};

module.exports = {
	getShippingZoneExample,
};
