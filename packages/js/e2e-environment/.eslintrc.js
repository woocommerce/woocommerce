module.exports = {
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
	env: {
		'jest/globals': true,
	},
	globals: {
		page: true,
		browser: true,
		context: true,
		jestPuppeteer: true,
	},
	plugins: [ 'jest', 'prettier' ],
	rules: {
		'prettier/prettier': 'error',
	},
};
