export default {
	$schema: 'https://schemas.wp.org/trunk/block.json',
	apiVersion: 2,
	name: 'woocommerce/product-empty',
	title: 'Product empty',
	category: 'widgets',
	description: 'The product empty.',
	keywords: [ 'products', 'empty', 'title' ],
	textdomain: 'default',
	attributes: {
		name: {
			type: 'string',
		},
	},
	supports: {
		align: false,
		html: false,
		// multiple: false,
		// reusable: false,
		inserter: false,
		lock: false,
	},
};
