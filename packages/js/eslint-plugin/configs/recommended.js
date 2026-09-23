/**
 * External dependencies
 */
const wordpress = require( '@wordpress/eslint-plugin' );
const tseslint = require( 'typescript-eslint' );

/**
 * Internal dependencies
 */
const customConfig = require( './custom' );

module.exports = [
	/*
	 * Brings WordPress' parser, globals, prettier, react, react-hooks, jsdoc and
	 * the fixupPluginRules-wrapped `import` and `react` plugins. eslint-plugin-import
	 * has no native ESLint v10 support, so it must only ever be used through the
	 * wrapped instance registered here — never registered again downstream.
	 */
	...wordpress.configs.recommended,
	/*
	 * WordPress scopes the TypeScript parser to `**‍/*.ts(x)`. WooCommerce applies it
	 * everywhere, so spread typescript-eslint's recommended config, whose first entry
	 * sets the parser and registers `@typescript-eslint` for all files.
	 */
	...tseslint.configs.recommended,
	...customConfig,
	{
		languageOptions: {
			globals: {
				wcSettings: 'readonly',
			},
			parserOptions: {
				ecmaFeatures: {
					jsx: true,
				},
			},
		},
		settings: {
			// List of modules that are externals in our webpack config.
			'import/core-modules': [
				'@woocommerce/settings',
				'lodash',
				'react',
			],
			react: {
				pragma: 'createElement',
				version: '18.3',
			},
		},
		rules: {
			radix: 'error',
			yoda: [ 'error', 'never' ],
			// temporary conversion to warnings until the below are all handled.
			'jsdoc/check-line-alignment': 'warn',
			'jsdoc/require-returns-check': 'warn',
			'@wordpress/i18n-text-domain': [
				'error',
				{
					allowedTextDomain: 'woocommerce',
				},
			],
			'@typescript-eslint/no-explicit-any': 'error',
			'@typescript-eslint/no-use-before-define': [ 'error' ],
			'@typescript-eslint/no-shadow': [ 'error' ],
			'@typescript-eslint/no-empty-function': 'off',
			camelcase: 'off',
			'no-use-before-define': 'off',
			'jsdoc/require-param': 'off',
			// Making use of typescript no-shadow instead, fixes issues with enum.
			'no-shadow': 'off',
			'@wordpress/no-unsafe-wp-apis': 'warn',
			'@wordpress/no-global-active-element': 'warn',
			'import/no-extraneous-dependencies': 'warn',
			'import/no-unresolved': 'warn',
			'jsdoc/check-tag-names': [
				'error',
				{
					definedTags: [
						'jest-environment',
						'filter',
						'action',
						'slotFill',
						'scope',
					],
				},
			],
			'@typescript-eslint/no-unused-vars': [
				'error',
				{
					varsIgnorePattern: 'createElement',
					ignoreRestSiblings: true,
				},
			],
			'react/react-in-jsx-scope': 'off',
		},
	},
	{
		files: [ '**/*.js', '**/*.jsx' ],
		rules: {
			'@typescript-eslint/no-var-requires': 'off',
		},
	},
	{
		files: [ '**/*.ts', '**/*.tsx' ],
		rules: {
			// Making use of typescript no-shadow instead.
			'no-shadow': 'off',
		},
	},
	{
		files: [ '**/stories/*.js', '**/stories/*.jsx', '**/docs/example.js' ],
		rules: {
			'react/react-in-jsx-scope': 'off',
		},
	},
];
