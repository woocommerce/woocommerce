/*
 * Attributes data
 * These attributes represent the attributes
 * that the user can assign to a product.
 */
const attributes = [
	{
		name: 'Color',
		terms: [
			{ name: 'Red', slug: 'red' },
			{ name: 'Blue', slug: 'blue' },
			{ name: 'Green', slug: 'green' },
		],
	},
	{
		name: 'Size',
		terms: [
			{ name: 'Small', slug: 'small' },
			{ name: 'Medium', slug: 'medium' },
			{ name: 'Large', slug: 'large' },
			{ name: 'Extra Large', slug: 'extra-large' },
			{ name: 'Extra Extra Large', slug: 'extra-extra-large' },
		],
	},
	{
		name: 'Style',
		terms: [
			{ name: 'Modern', slug: 'modern' },
			{ name: 'Classic', slug: 'classic' },
			{ name: 'Vintage', slug: 'vintage' },
		],
	},
];

module.exports = attributes;
