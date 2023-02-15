export default {
	$schema: 'https://schemas.wp.org/trunk/block.json',
	apiVersion: 2,
	name: 'woocommerce/product-images',
	title: 'Product images',
	category: 'widgets',
	description: 'The product images.',
	keywords: [ 'products', 'images' ],
	textdomain: 'default',
	attributes: {
		content: {
			type: 'string',
		},
		placeholder: {
			type: 'string',
		},
	},
	supports: {},
};
