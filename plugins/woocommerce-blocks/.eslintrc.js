module.exports = {
	extends: [
		'plugin:@wordpress/eslint-plugin/recommended',
		'prettier',
		'plugin:jest/recommended',
		'plugin:react-hooks/recommended',
	],
	env: {
		'jest/globals': true,
	},
	globals: {
		wcSettings: 'readonly',
		wcStoreApiNonce: 'readonly',
		page: true,
		browser: true,
		context: true,
		jestPuppeteer: true,
		fetchMock: true,
		jQuery: 'readonly',
		IntersectionObserver: 'readonly',
	},
	plugins: [ 'jest', 'woocommerce' ],
	rules: {
		'@wordpress/dependency-group': 'off',
		'woocommerce/dependency-group': 'error',
		'woocommerce/feature-flag': 'off',
		'valid-jsdoc': 'off',
		radix: 'error',
		yoda: [ 'error', 'never' ],
	},
};
