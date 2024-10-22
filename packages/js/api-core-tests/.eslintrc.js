module.exports = {
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
	ignorePatterns: [ '**/test/*.ts', '**/test/*.tsx' ],
	rules: {
		'jsdoc/check-tag-names': 'off',
	},
	root: true,
};
