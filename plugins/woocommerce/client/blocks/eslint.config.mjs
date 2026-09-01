/**
 * External dependencies
 */
import { globalIgnores } from 'eslint/config';
import globals from 'globals';
import storybook from 'eslint-plugin-storybook';
import youDontNeedLodashUnderscore from 'eslint-plugin-you-dont-need-lodash-underscore';

/**
 * Internal dependencies
 */
import woocommerce from '@woocommerce/eslint-config';

const restrictedImports = [
	{
		name: 'lodash',
		importNames: [
			'camelCase',
			'capitalize',
			'castArray',
			'chunk',
			'clamp',
			'clone',
			'cloneDeep',
			'compact',
			'concat',
			'countBy',
			'debounce',
			'deburr',
			'defaults',
			'defaultTo',
			'delay',
			'difference',
			'differenceWith',
			'dropRight',
			'each',
			'escape',
			'escapeRegExp',
			'every',
			'extend',
			'filter',
			'find',
			'findIndex',
			'findKey',
			'findLast',
			'first',
			'flatMap',
			'flatten',
			'flattenDeep',
			'flow',
			'flowRight',
			'forEach',
			'fromPairs',
			'has',
			'identity',
			'includes',
			'invoke',
			'isArray',
			'isBoolean',
			'isEqual',
			'isFinite',
			'isFunction',
			'isMatch',
			'isNil',
			'isNumber',
			'isObject',
			'isObjectLike',
			'isPlainObject',
			'isString',
			'isUndefined',
			'keyBy',
			'keys',
			'last',
			'lowerCase',
			'map',
			'mapKeys',
			'maxBy',
			'memoize',
			'merge',
			'negate',
			'noop',
			'nth',
			'omit',
			'omitBy',
			'once',
			'orderBy',
			'overEvery',
			'partial',
			'partialRight',
			'pick',
			'pickBy',
			'random',
			'reduce',
			'reject',
			'repeat',
			'reverse',
			'setWith',
			'size',
			'snakeCase',
			'some',
			'sortBy',
			'startCase',
			'startsWith',
			'stubFalse',
			'stubTrue',
			'sum',
			'sumBy',
			'take',
			'throttle',
			'times',
			'toString',
			'trim',
			'truncate',
			'unescape',
			'unionBy',
			'uniq',
			'uniqBy',
			'uniqueId',
			'uniqWith',
			'upperFirst',
			'values',
			'without',
			'words',
			'xor',
			'zip',
		],
		message:
			'This Lodash method is not recommended. Please use native functionality instead. If using `memoize`, please use `memize` instead.',
	},
];

const coreModules = [
	'@woocommerce/base-context',
	'@woocommerce/base-components',
	'@woocommerce/base-components/cart-checkout',
	'@woocommerce/block-data',
	'@woocommerce/blocks-checkout',
	'@woocommerce/blocks-checkout-events',
	'@woocommerce/blocks-components',
	'@woocommerce/blocks-registry',
	'@woocommerce/block-settings',
	'@woocommerce/email-editor',
	'@woocommerce/price-format',
	'@woocommerce/settings',
	'@woocommerce/shared-context',
	'@woocommerce/shared-hocs',
	'@woocommerce/stores/store-notices',
	'@woocommerce/stores/woocommerce/cart',
	'@woocommerce/stores/woocommerce/products',
	'@woocommerce/stores/woocommerce/shopper-lists',
	'@woocommerce/tracks',
	'@woocommerce/data',
	'@woocommerce/customer-effort-score',
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
	'@wordpress/wordcount',
	'@woocommerce/blocks-test-utils',
	'babel-jest',
	'dotenv',
	'lodash/kebabCase',
	'lodash',
	'prop-types',
	'react',
	'requireindex',
	'react-transition-group',
];

const TEST_FILES = [
	'assets/js/**/test/**/*.{js,jsx,ts,tsx}',
	'assets/js/**/*.test.{js,jsx,ts,tsx}',
];

export default [
	// node_modules is ignored by default.
	globalIgnores( [
		'build',
		'build-module',
		'coverage',
		'languages',
		'vendor',
		'legacy',
		'reports',
		'storybook/dist',
		'assets/js/interactivity',
		'tests/e2e-jest/specs/backend/__fixtures__',
		'tests/e2e-jest/specs/backend/__snapshots__',
	] ),
	/*
	 * The shared config already registers the jest, @typescript-eslint and
	 * fixupPluginRules-wrapped `import` plugins, and applies the TypeScript
	 * parser globally. The eslintrc this replaces re-registered all three via
	 * `plugins:` and `plugin:jest/recommended` / `plugin:@typescript-eslint/recommended`
	 * / `plugin:import/errors`. Registering second copies is fatal under ESLint
	 * v10 (eslint-plugin-import has no v10 support) and otherwise trips
	 * "Cannot redefine plugin", so those rules are set on the inherited plugins.
	 */
	...woocommerce,
	...storybook.configs[ 'flat/recommended' ],
	{
		/*
		 * This plugin ships only eslintrc-style configs, whose `plugins` is an
		 * array of names rather than a map, so `compatible` cannot be spread as a
		 * flat config. Register the plugin and take its rules instead. Those rules
		 * call no APIs ESLint 10 removed, so unlike `import` and `react` this one
		 * needs no @eslint/compat fixup.
		 */
		plugins: {
			'you-dont-need-lodash-underscore': youDontNeedLodashUnderscore,
		},
		rules: youDontNeedLodashUnderscore.configs.compatible.rules,
	},
	{
		languageOptions: {
			globals: {
				...globals.browser,
				...globals.jest,
				wcBlocksMiddlewareConfig: 'readonly',
				fetchMock: 'writable',
				jQuery: 'readonly',
				IntersectionObserver: 'readonly',
				// @todo Move E2E related ESLint configuration into custom config.
				//
				// We should have linting properties only included for files that they
				// are specific to as opposed to globally.
				page: 'readonly',
				browser: 'readonly',
				context: 'readonly',
			},
		},
		settings: {
			jsdoc: { mode: 'typescript' },
			// List of modules that are externals in our webpack config.
			// This helps the `import/no-extraneous-dependencies` and
			//`import/no-unresolved` rules account for them.
			'import/core-modules': coreModules,
			'import/resolver': {
				node: {},
				webpack: {},
				typescript: {},
			},
		},
		rules: {
			/*
			 * Relaxed with the rest of the upgrade-surfaced rules; see
			 * tools/eslint-config. eslint-plugin-react-hooks v7 reports more here
			 * than v4 did.
			 */
			'react-hooks/exhaustive-deps': 'warn',
			'react/jsx-fragments': [ 'error', 'syntax' ],
			'@wordpress/no-global-active-element': 'warn',
			'@wordpress/i18n-text-domain': [
				'error',
				{
					allowedTextDomain: [ 'woocommerce' ],
				},
			],
			'no-restricted-imports': [
				'error',
				{
					paths: restrictedImports,
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
	},
	{
		files: [ '**/tests/e2e-jest/**' ],
		rules: {
			'jest/no-disabled-tests': 'off',
		},
	},
	{
		files: [ '**/bin/**.js', '**/storybook/**.js', '**/stories/**.js' ],
		// These build/tooling scripts run in Node; eslint-env comments are gone in v9+.
		languageOptions: {
			globals: { ...globals.node },
		},
		rules: {
			'you-dont-need-lodash-underscore/omit': 'off',
		},
	},
	{
		files: TEST_FILES,
		rules: {
			'@typescript-eslint/no-non-null-assertion': 'error',
			'jest/no-mocks-import': 'off',
			// With React Testing library, it is expected use expect() in the waitFor() function: https://testing-library.com/docs/dom-testing-library/api-async/
			'jest/no-standalone-expect': 'off',
		},
	},
	{
		files: [ '**/*.ts', '**/*.tsx' ],
		// `excludedFiles` in eslintrc.
		ignores: TEST_FILES,
		settings: {
			'import/parsers': {
				'@typescript-eslint/parser': [ '.ts', '.tsx' ],
			},
			'import/resolver': {
				typescript: {}, // this loads <rootdir>/tsconfig.json to eslint
			},
			'import/core-modules': [
				...coreModules,
				// We should lint these modules imports, but the types are way out of date.
				// To support us not inadvertently introducing new import errors this lint exists, but to avoid
				// having to fix hundreds of import errors for @wordpress packages we ignore them.
				'@wordpress/components',
				'@wordpress/element',
				'@wordpress/blocks',
				'@wordpress/notices',
			],
		},
		rules: {
			'@typescript-eslint/no-explicit-any': 'error',
			'@typescript-eslint/no-non-null-assertion': 'error',
			'no-use-before-define': 'off',
			'@typescript-eslint/no-use-before-define': [ 'error' ],
			'jsdoc/require-param': 'off',
			'no-shadow': 'off',
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
			/*
			 * These five were `extends: [ 'plugin:import/errors' ]`, set here on the
			 * inherited `import` plugin. WordPress' TypeScript config turns
			 * no-unresolved, default and named off for speed; blocks wants them on.
			 *
			 * no-unresolved and named are warnings for now: the newer
			 * eslint-plugin-import reports far more than the pinned 2.28 did. Tracked
			 * with the rest of the upgrade-surfaced rules; see tools/eslint-config.
			 */
			'import/no-unresolved': 'warn',
			'import/default': 'error',
			// Explicitly turning this on because we need to catch import errors that we don't catch with TS right now
			// due to it only being run in a checking capacity.
			'import/named': 'warn',
			//  These should absolutely be linted, but due to there being a large number
			//  of changes needed to fix for example `export *` of packages with only default exports
			//  we will leave these as warnings for now until those can be fixed.
			'import/namespace': 'warn',
			'import/export': 'warn',
			'import/no-duplicates': 'warn',
			'@typescript-eslint/no-unused-vars': 'warn',
			'@typescript-eslint/no-shadow': 'warn',
		},
	},
	{
		files: [ '**/frontend.ts' ],
		rules: {
			'@typescript-eslint/no-use-before-define': 'off',
		},
	},
	{
		files: [ 'assets/js/mapped-types.ts' ],
		rules: {
			'@typescript-eslint/no-explicit-any': 'off',
			'@typescript-eslint/no-shadow': 'off',
			'no-shadow': 'off',
		},
	},
	{
		files: [
			'assets/js/blocks/cart/**/block.{ts,tsx}',
			'assets/js/blocks/checkout/**/block.{ts,tsx}',
			'assets/js/blocks/**/frontend.{ts,tsx}',
			'assets/js/base/**/*.{ts,tsx}',
		],
		// `excludedFiles` in eslintrc.
		ignores: [
			'**/edit.{ts,tsx}',
			'**/*.test.{ts,tsx}',
			'**/test/**',
			'**/stories/**',
		],
		rules: {
			'no-restricted-imports': [
				'error',
				{
					paths: [
						...restrictedImports,
						{
							name: '@wordpress/components',
							message:
								'@wordpress/components must not ship to the storefront. Use it only in edit.tsx.',
						},
					],
				},
			],
		},
	},
];
