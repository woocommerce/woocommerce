/**
 * This file contains objects that can be used as test data for scenarios around creating, retrieivng, updating, and deleting products.
 *
 * For more details on the Product properties, see:
 *
 * https://woocommerce.github.io/woocommerce-rest-api-docs/#products
 *
 */

export interface SimpleProduct {
	name: string;
	regular_price: string;
	description: string;
	short_description: string;
}

export interface VirtualProduct {
	name: string;
	regular_price: string;
	virtual: boolean;
}

export interface ProductAttribute {
	name: string;
	visible: boolean;
	variation: boolean;
	options: string[];
}

export interface VariableProduct {
	name: string;
	type: string;
	attributes: ProductAttribute[];
}

export interface ExternalProduct {
	name: string;
	regular_price: string;
	type: string;
}

export interface GroupedProduct {
	name: string;
	type: string;
}

/**
 * A simple, physical product.
 */
export const simpleProduct: SimpleProduct = {
	name: 'A Simple Product',
	regular_price: '25',
	description: 'Description for this simple product.',
	short_description: 'Shorter description.',
};

/**
 * A virtual product
 */
export const virtualProduct: VirtualProduct = {
	name: 'A Virtual Product',
	regular_price: '10',
	virtual: true,
};

/**
 * A variable product
 */
export const variableProduct: VariableProduct = {
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
export const externalProduct: ExternalProduct = {
	name: 'An External Product',
	regular_price: '1.00',
	type: 'external',
};

/**
 * Grouped product example
 */
export const groupedProduct: GroupedProduct = {
	name: 'A Grouped Product',
	type: 'grouped',
};
