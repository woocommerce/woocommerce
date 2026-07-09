/**
 * External dependencies
 */
import globals from 'globals';

/**
 * Internal dependencies
 */
import woocommerce from '@woocommerce/eslint-config';

export default [
	{
		/*
		 * client/admin and client/blocks carry their own configs.
		 * node_modules is ignored by default.
		 */
		ignores: [
			'**/*.min.js',
			'**/.wireit/**',
			'**/vendor/**',
			'assets/**',
			'bin/composer/**',
			'client/admin/**',
			'client/blocks/**',
			'client/legacy/**',
			'includes/gateways/**',
		],
	},
	/*
	 * The eslintrc this replaces declared neither `extends` nor `root`: it
	 * inherited WooCommerce's preset by cascading to the repo root config. Flat
	 * config does not cascade, so spread it explicitly.
	 */
	...woocommerce,
	{
		/*
		 * The eslintrc set `parser: '@babel/eslint-parser'`. That parser's v7 line
		 * calls scopeManager.addGlobals, which ESLint 10 removed, and v8 requires
		 * @babel/core ^8 while this repo is pinned to 7.x. WordPress works around
		 * it with an unexported compat shim. The TypeScript parser the shared
		 * config already installs parses every source here, so inherit it.
		 */
		languageOptions: {
			ecmaVersion: 8,
			globals: {
				...globals.browser,
				...globals.node,
				...globals.es2015,
				wp: 'writable',
				wpApiSettings: 'writable',
				wcSettings: 'writable',
			},
			parserOptions: {
				ecmaFeatures: {
					jsx: true,
				},
			},
		},
		rules: {
			camelcase: 'off',
			indent: 'off',
			'no-console': 'warn',
		},
	},
	{
		files: [ 'tests/e2e/**/*.spec.js' ],
		rules: {
			// Renamed from `no-test-callback` in eslint-plugin-jest v24.
			'jest/no-done-callback': 'off',
			'@wordpress/no-unsafe-wp-apis': 'off',
			'import/no-extraneous-dependencies': 'off',
			'import/no-unresolved': 'off',
		},
	},
];
