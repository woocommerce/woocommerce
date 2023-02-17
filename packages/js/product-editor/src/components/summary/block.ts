export default {
	$schema: 'https://schemas.wp.org/trunk/block.json',
	apiVersion: 2,
	name: 'woocommerce/product-summary',
	title: 'Product summary',
	category: 'widgets',
	description: 'The product summary.',
	keywords: [ 'products', 'summary' ],
	textdomain: 'default',
	attributes: {
		content: {
			type: 'string',
		},
		placeholder: {
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
