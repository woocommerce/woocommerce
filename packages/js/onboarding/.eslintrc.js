module.exports = {
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
	root: true,
	settings: {
		'import/core-modules': [
			'@woocommerce/experimental',
			'@woocommerce/components',
			'@woocommerce/tracks',
		],
		'import/resolver': {
			node: {},
			webpack: {},
			typescript: {},
		},
	},
};
