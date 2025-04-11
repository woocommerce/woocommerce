module.exports = {
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
	root: true,
	settings: {
		'import/core-modules': [
			'@woocommerce/number',
			'@woocommerce/settings',
		],
		'import/resolver': {
			node: {},
			typescript: {},
		},
	},
};
