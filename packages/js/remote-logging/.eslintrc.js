module.exports = {
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
	root: true,
	ignorePatterns: [ '**/test/*.ts', '**/test/*.tsx' ],
	settings: {
		'import/core-modules': [ '@woocommerce/settings' ],
		'import/resolver': {
			node: {},
			typescript: {},
		},
	},
};
