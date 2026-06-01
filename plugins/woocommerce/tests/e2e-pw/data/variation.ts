/**
 * A basic product variation.
 *
 * For more details on the product variation properties, see:
 *
 * https://developer.woocommerce.com/docs/apis/rest-api/v3/product-variations/
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

export const getVariationExample = () => {
	return variation;
};
