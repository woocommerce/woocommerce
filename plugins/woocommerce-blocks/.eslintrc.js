module.exports = {
	root: true,
	extends: [
		'plugin:@woocommerce/eslint-plugin/recommended',
		'plugin:you-dont-need-lodash-underscore/compatible',
	],
	globals: {
		wcBlocksMiddlewareConfig: 'readonly',
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
			'@woocommerce/price-format',
			'@woocommerce/settings',
			'@woocommerce/shared-context',
			'@woocommerce/shared-hocs',
			'@woocommerce/data',
			'@wordpress/a11y',
			'@wordpress/api-fetch',
			'@wordpress/block-editor',
			'@wordpress/compose',
			'@wordpress/data',
			'@wordpress/core-data',
			'@wordpress/editor',
			'@wordpress/escape-html',
			'@wordpress/hooks',
			'@wordpress/keycodes',
			'@wordpress/url',
			'@woocommerce/blocks-test-utils',
			'@woocommerce/e2e-utils',
			'babel-jest',
			'dotenv',
			'jest-environment-puppeteer',
			'lodash/kebabCase',
			'lodash',
			'prop-types',
			'react',
			'requireindex',
			'react-transition-group',
		],
		'import/resolver': {
			node: {},
			webpack: {},
			typescript: {},
		},
	},
	rules: {
		'woocommerce/feature-flag': 'off',
		'react-hooks/exhaustive-deps': 'error',
		'react/jsx-fragments': [ 'error', 'syntax' ],
		'@wordpress/no-global-active-element': 'warn',
		'@wordpress/i18n-text-domain': [
			'error',
			{
				allowedTextDomain: [ 'woo-gutenberg-products-block' ],
			},
		],
		'@typescript-eslint/no-restricted-imports': [
			'error',
			{
				paths: [
					{
						name: 'react',
						message:
							'Please use React API through `@wordpress/element` instead.',
						allowTypeImports: true,
					},
				],
			},
		],
		camelcase: [
			'error',
			{
				properties: 'never',
				ignoreGlobals: true,
			},
		],
		'react/react-in-jsx-scope': 'off',
	},
	overrides: [
		{
			files: [ '**/bin/**.js', '**/storybook/**.js', '**/stories/**.js' ],
			rules: {
				'you-dont-need-lodash-underscore/omit': 'off',
			},
		},
		{
			files: [ '*.ts', '*.tsx' ],
			parser: '@typescript-eslint/parser',
			extends: [
				'plugin:@woocommerce/eslint-plugin/recommended',
				'plugin:you-dont-need-lodash-underscore/compatible',
				'plugin:@typescript-eslint/recommended',
			],
			rules: {
				'@typescript-eslint/no-explicit-any': 'error',
				'no-use-before-define': 'off',
				'@typescript-eslint/no-use-before-define': [ 'error' ],
				'jsdoc/require-param': 'off',
				'no-shadow': 'off',
				'@typescript-eslint/no-shadow': [ 'error' ],
				'@typescript-eslint/no-unused-vars': [
					'error',
					{ ignoreRestSiblings: true },
				],
				camelcase: 'off',
				'@typescript-eslint/naming-convention': [
					'error',
					{
						selector: [ 'method', 'variableLike' ],
						format: [ 'camelCase', 'PascalCase', 'UPPER_CASE' ],
						leadingUnderscore: 'allowSingleOrDouble',
						filter: {
							regex: 'webpack_public_path__',
							match: false,
						},
					},
					{
						selector: 'typeProperty',
						format: [ 'camelCase', 'snake_case' ],
						filter: {
							regex: 'API_FETCH_WITH_HEADERS|Block',
							match: false,
						},
					},
				],
				'react/react-in-jsx-scope': 'off',
			},
		},
		{
			files: [ './assets/js/mapped-types.ts' ],
			rules: {
				'@typescript-eslint/no-explicit-any': 'off',
				'@typescript-eslint/no-shadow': 'off',
				'no-shadow': 'off',
			},
		},
	],
};
