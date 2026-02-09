module.exports = {
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
	root: true,
	settings: {
		'import/core-modules': [ '@woocommerce/components' ],
		'import/resolver': {
			node: {},
			webpack: {},
			typescript: {},
		},
	},
};
