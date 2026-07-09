/**
 * External dependencies
 */
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import globals from 'globals';
import playwright from 'eslint-plugin-playwright';

/**
 * Internal dependencies
 */
import woocommerce from '@woocommerce/eslint-config';
import noRawPlaywrightTestImport from './tests/e2e/rules/blocks/no-raw-playwright-test-import.js';

const __dirname = path.dirname( fileURLToPath( import.meta.url ) );

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

	/*
	 * tests/e2e had its own .eslintrc.cjs. It has no package.json, so nothing
	 * ever linted it on its own: it was reached by eslintrc cascade from here.
	 * Flat config does not cascade, so its rules live here, scoped to its files.
	 */
	{
		files: [ 'tests/e2e/**' ],
		...playwright.configs[ 'flat/recommended' ],
	},
	{
		files: [ 'tests/e2e/**' ],
		rules: {
			'playwright/no-wait-for-timeout': 'error',
			'playwright/no-skipped-test': 'off',
			'no-console': 'off',
			// Renamed from `no-test-callback` in eslint-plugin-jest v24.
			'jest/no-done-callback': 'off',
			'jest/no-disabled-tests': 'off',
			'jest/valid-expect': 'off',
			'jest/expect-expect': 'off',
			'jest/no-standalone-expect': 'off',
			'jest/valid-title': 'off',
			'testing-library/await-async-utils': 'off',
			/*
			 * The e2e tests use dependencies from the parent woocommerce package
			 * (that directory has no package.json of its own). Point packageDir at
			 * directories that do contain one: eslint-plugin-import throws on the
			 * first missing entry and aborts the whole dependency merge.
			 */
			'import/no-extraneous-dependencies': [
				'warn',
				{
					packageDir: [
						__dirname,
						path.resolve( __dirname, '../..' ),
					],
				},
			],
		},
	},
	{
		files: [ 'tests/e2e/**/*.ts', 'tests/e2e/**/*.tsx' ],
		rules: {
			'@typescript-eslint/no-explicit-any': 'off',
		},
	},
	/*
	 * Blocks e2e subtree. `eslint-plugin-rulesdir` has no flat-config equivalent
	 * - it works by mutating a module-level RULES_DIR - so the local rule is
	 * imported and registered directly instead.
	 */
	{
		files: [ 'tests/e2e/tests/blocks/**', 'tests/e2e/utils/blocks/**' ],
		languageOptions: {
			parserOptions: {
				tsconfigRootDir: path.join( __dirname, 'tests/e2e' ),
				project: './tsconfig.blocks.json',
			},
		},
		plugins: {
			'woocommerce-e2e': {
				rules: {
					'no-raw-playwright-test-import': noRawPlaywrightTestImport,
				},
			},
		},
		rules: {
			'woocommerce-e2e/no-raw-playwright-test-import': 'error',
			// Since we're restoring the database for each test, hooks other
			// than `beforeEach` don't make sense.
			// See https://github.com/woocommerce/woocommerce/pull/46432.
			'playwright/no-hooks': [ 'error', { allow: [ 'beforeEach' ] } ],
			'no-restricted-syntax': [
				'error',
				{
					selector: 'CallExpression[callee.property.name="$"]',
					message: '`$` is discouraged, please use `locator` instead',
				},
				{
					selector: 'CallExpression[callee.property.name="$$"]',
					message:
						'`$$` is discouraged, please use `locator` instead',
				},
				{
					selector:
						'CallExpression[callee.object.name="page"][callee.property.name="waitForTimeout"]',
					message: 'Prefer page.locator instead.',
				},
			],
		},
	},
];
