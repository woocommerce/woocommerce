module.exports = {
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
	root: true,
	settings: {
		'import/core-modules': [
			'@woocommerce/components',
			'@wordpress/components',
		],
		'import/resolver': {
			node: {},
			webpack: {},
			typescript: {},
		},
	},
};
