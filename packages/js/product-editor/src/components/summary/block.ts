export default {
	$schema: 'https://schemas.wp.org/trunk/block.json',
	apiVersion: 2,
	name: 'woocommerce/product-summary',
	title: 'Product summary',
	category: 'widgets',
	description: 'The product summary.',
	keywords: [ 'products', 'summary' ],
	textdomain: 'default',
	attributes: {},
	ancestor: [ 'woocommerce/product-form' ],
	supports: {},
	style: 'wp-paragraph',
};
