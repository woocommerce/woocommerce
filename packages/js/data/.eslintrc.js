module.exports = {
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
	root: true,
	settings: {
		'import/core-modules': [
			'@woocommerce/date',
			'@woocommerce/navigation',
			'@woocommerce/tracks',
			'@wordpress/api-fetch',
			'@wordpress/core-data',
			'@wordpress/data',
			'@automattic/data-stores',
			'redux',
		],
		'import/resolver': {
			node: {},
			typescript: {},
		},
	},
};
