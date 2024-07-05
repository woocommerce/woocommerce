module.exports = {
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
	root: true,
	settings: {
		'import/core-modules': [
			'@woocommerce/date',
			'@woocommerce/navigation',
			'@woocommerce/tracks',
		],
		'import/resolver': {
			node: {},
			typescript: {},
		},
	},
};
