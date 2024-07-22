module.exports = {
	extends: [
		'plugin:@typescript-eslint/recommended',
		'plugin:@woocommerce/eslint-plugin/recommended',
	],
	root: true,
	settings: {
		'import/core-modules': [ '@woocommerce/e2e-utils' ],
		'import/resolver': {
			node: {},
			typescript: {},
		},
	},
};
