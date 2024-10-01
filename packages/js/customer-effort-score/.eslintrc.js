module.exports = {
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
	root: true,
	settings: {
		'import/core-modules': [
			'@woocommerce/data',
			'@woocommerce/experimental',
			'@woocommerce/navigation',
			'@woocommerce/tracks',
			'@testing-library/react',
		],
		'import/resolver': {
			node: {},
			webpack: {},
			typescript: {},
		},
	},
};
