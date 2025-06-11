module.exports = {
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
	plugins: [ 'jest' ],
	root: true,
	env: {
		'jest/globals': true,
	},
	rules: {
		'jest/expect-expect': 'off',
		'jest/no-disabled-tests': 'off',
		'@typescript-eslint/no-shadow': 'off',
	},
	globals: {
		page: true,
	},
};
