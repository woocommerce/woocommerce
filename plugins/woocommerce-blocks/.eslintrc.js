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
		// @todo Remove temporary disabling of various eslint rules.
		// To keep pull request reviews smaller and changes more precise, new rules
		// added via the adoption of `@woocommerce/eslint-plugin` that are failing
		// will be handled in individual pulls. The following rules need to be turned
		// back on (via individual pulls):
		// - jsdoc/require-param
		// - jsdoc/check-tag-names
		// - jsdoc/check-param-names
		// - jsdoc/require-property-description
		// - jsdoc/valid-types
		// - jsdoc/require-property
		// - jsdoc/no-undefined-types
		// - jsdoc/check-types
		// - jsdoc/require-returns-description
		// - jsdoc/require-param-type
		// - jsdoc/require-returns-type
		// - jsdoc/newline-after-description
		// - @wordpress/i18n-translator-comments
		// - @wordpress/valid-sprintf
		// - @worpdress/no-unused-vars-before-return
		// - testing-library/no-await-sync-query
		// - @woocommerce/dependency-group
		'@wordpress/i18n-translator-comments': 'off',
		'@wordpress/valid-sprintf': 'off',
		'@wordpress/no-unused-vars-before-return': 'off',
		'testing-library/no-await-sync-query': 'off',
		'@woocommerce/dependency-group': 'off',
	},
};
