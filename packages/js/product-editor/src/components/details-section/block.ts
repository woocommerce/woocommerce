export default {
	$schema: 'https://schemas.wp.org/trunk/block.json',
	apiVersion: 2,
	name: 'woocommerce/product-details-section',
	title: 'Product details section',
	category: 'widgets',
	description: 'A product section containing grouped fields around details.',
	keywords: [ 'products', 'section', 'details' ],
	textdomain: 'default',
	attributes: {
		title: {
			type: 'string',
		},
		description: {
			type: 'string',
		},
	},
	supports: {},
};
