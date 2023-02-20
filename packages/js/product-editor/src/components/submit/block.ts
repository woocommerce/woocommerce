export default {
	$schema: 'https://schemas.wp.org/trunk/block.json',
	apiVersion: 2,
	name: 'woocommerce/product-submit',
	title: 'Submit',
	category: 'widgets',
	description: 'The product name.',
	keywords: [ 'products', 'name', 'title' ],
	textdomain: 'default',
	attributes: {
		value: {
			type: 'string',
		},
	},
	supports: {},
	style: 'wp-paragraph',
};
