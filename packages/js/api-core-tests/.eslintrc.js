module.exports = {
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
	rules: {
		'jsdoc/check-tag-names': 'off',
		'prettier/prettier': 'error',
	},
	root: true,
	plugins: [ 'prettier' ],
};
