/**
 * A basic product variation.
 *
 * For more details on the product variation properties, see:
 *
 * https://woocommerce.github.io/woocommerce-rest-api-docs/#product-variations
 *
 */

export interface VariationAttribute {
	name: string;
	option: string;
}

export interface Variation {
	regular_price: string;
	attributes: VariationAttribute[];
}

const variation: Variation = {
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

export const getVariationExample = (): Variation => {
	return variation;
};
