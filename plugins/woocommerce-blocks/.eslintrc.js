module.exports = {
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
	globals: {
		wcStoreApiNonce: 'readonly',
		fetchMock: true,
		jQuery: 'readonly',
		IntersectionObserver: 'readonly',
		// @todo Move E2E related ESLint configuration into custom config.
		//
		// We should have linting properties only included for files that they
		// are specific to as opposed to globally.
		page: 'readonly',
		browser: 'readonly',
		context: 'readonly',
		jestPuppeteer: 'readonly',
	},
	settings: {
		jsdoc: { mode: 'typescript' },
	},
	rules: {
		'woocommerce/feature-flag': 'off',
	},
};
