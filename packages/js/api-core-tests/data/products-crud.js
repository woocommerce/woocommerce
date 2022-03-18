/**
 * This file contains objects that can be used as test data for scenarios around creating, retrieivng, updating, and deleting products.
 *
 * For more details on the Product properties, see:
 *
 * https://woocommerce.github.io/woocommerce-rest-api-docs/#products
 *
 */

/**
 * A simple, physical product.
 */
const simpleProduct = {
	name: 'A Simple Product',
	regular_price: '25',
	description: 'Description for this simple product.',
	short_description: 'Shorter description.',
};

/**
 * A virtual product
 */
const virtualProduct = {
	name: 'A Virtual Product',
	regular_price: '10',
	virtual: true,
};

/**
 * A variable product
 */
const variableProduct = {
	name: 'A Variable Product',
	type: 'variable',
	attributes: [
		{
			name: 'Colour',
			visible: true,
			variation: true,
			options: [ 'Red', 'Green', 'Blue' ],
		},
		{
			name: 'Size',
			visible: true,
			variation: true,
			options: [ 'Small', 'Medium', 'Large' ],
		},
		{
			name: 'Logo',
			visible: true,
			variation: true,
			options: [ 'Woo', 'WordPress' ],
		},
	],
};

/**
 * External product example
 */
const externalProduct = {
	name: 'An External Product',
	regular_price: '1.00',
	type: 'external',
};

/**
 * Grouped product example
 */
const groupedProduct = {
	name: 'A Grouped Product',
	type: 'grouped',
};

module.exports = {
	simpleProduct,
	virtualProduct,
	variableProduct,
	externalProduct,
	groupedProduct,
};
