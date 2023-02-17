export default {
	$schema: 'https://schemas.wp.org/trunk/block.json',
	apiVersion: 2,
	name: 'woocommerce/product-section',
	title: 'Product section',
	category: 'widgets',
	description: 'A product section containing a group of related fields.',
	keywords: [ 'products', 'section' ],
	textdomain: 'default',
	attributes: {
		description: {
			type: 'string',
		},
		title: {
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
