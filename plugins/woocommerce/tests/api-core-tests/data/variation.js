/**
 * A basic product variation.
 *
 * For more details on the product variation properties, see:
 *
 * https://woocommerce.github.io/woocommerce-rest-api-docs/#product-variations
 *
 */
const variation = {
	regular_price: '1.00',
	attributes: [
		{
			name: 'Size',
			option: 'Large',
		},
		{
			name: 'Colour',
			option: 'Red',
		},
	],
};

const getVariationExample = () => {
	return variation;
};

module.exports = {
	getVariationExample,
};
