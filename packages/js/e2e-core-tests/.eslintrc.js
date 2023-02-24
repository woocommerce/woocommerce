module.exports = {
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
	plugins: [ 'jest', 'prettier' ],
	root: true,
	env: {
		'jest/globals': true,
	},
	globals: {
		page: true,
	},
	rules: {
		'prettier/prettier': 'error',
	},
};
