module.exports = {
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
	ignorePatterns: [ '**/jest.*' ],
	env: {
		'jest/globals': true,
	},
	globals: {
		page: true,
		browser: true,
		context: true,
		jestPuppeteer: true,
	},
	plugins: [ 'jest' ],
};
