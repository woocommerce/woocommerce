export default {
	$schema: 'https://schemas.wp.org/trunk/block.json',
	apiVersion: 2,
	name: 'woocommerce/product-list-price',
	title: 'Product list price',
	category: 'widgets',
	description: 'The product list price.',
	keywords: [ 'products', 'price' ],
	textdomain: 'default',
	attributes: {
		name: {
			type: 'string',
		},
	},
	supports: {
		align: false,
		html: false,
		multiple: false,
		reusable: false,
		inserter: false,
		lock: false,
	},
};
