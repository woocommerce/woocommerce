module.exports = {
	extends: [
		'plugin:@woocommerce/eslint-plugin/recommended',
		'plugin:you-dont-need-lodash-underscore/compatible',
	],
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
		// List of modules that are externals in our webpack config.
		// This helps the `import/no-extraneous-dependencies` and
		//`import/no-unresolved` rules account for them.
		'import/core-modules': [
			'@woocommerce/block-data',
			'@woocommerce/blocks-checkout',
			'@woocommerce/settings',
			'@woocommerce/shared-context',
			'@woocommerce/shared-hocs',
			'@woocommerce/knobs',
			'@wordpress/a11y',
			'@wordpress/api-fetch',
			'@wordpress/block-editor',
			'@wordpress/compose',
			'@wordpress/data',
			'@wordpress/escape-html',
			'@wordpress/hooks',
			'@wordpress/keycodes',
			'@wordpress/url',
			'babel-jest',
			'dotenv',
			'jest-environment-puppeteer',
			'lodash/kebabCase',
			'lodash',
			'prop-types',
			'react',
			'requireindex',
		],
		'import/resolver': {
			node: {},
			webpack: {},
		},
	},
	rules: {
		'woocommerce/feature-flag': 'off',
		'react-hooks/exhaustive-deps': 'error',
		'react/jsx-fragments': [ 'error', 'syntax' ],
		'@wordpress/no-global-active-element': 'warn',
	},
	overrides: [
		{
			files: [ '**/bin/**.js', '**/storybook/**.js', '**/stories/**.js' ],
			rules: {
				'you-dont-need-lodash-underscore/omit': 'off',
			},
		},
	],
};
