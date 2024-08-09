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
			'orderby',
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
	{
		name: 'classnames',
		message:
			"Please use `clsx` instead. It's a lighter and faster drop-in replacement for `classnames`.",
	},
];

module.exports = {
	env: {
		browser: true,
		jest: true,
	},
	root: true,
	extends: [
		'plugin:@woocommerce/eslint-plugin/recommended',
		'plugin:you-dont-need-lodash-underscore/compatible',
		'plugin:storybook/recommended',
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
			'@woocommerce/blocks-components',
			'@woocommerce/price-format',
			'@woocommerce/settings',
			'@woocommerce/shared-context',
			'@woocommerce/shared-hocs',
			'@woocommerce/tracks',
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
	overrides: [
		{
			files: [ '**/tests/e2e-jest/**' ],
			rules: {
				'jest/no-disabled-tests': 'off',
			},
		},
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
				'plugin:import/errors',
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
				// Explicitly turning this on because we need to catch import errors that we don't catch with TS right now
				// due to it only being run in a checking capacity.
				'import/named': 'error',
				//  These should absolutely be linted, but due to there being a large number
				//  of changes needed to fix for example `export *` of packages with only default exports
				//  we will leave these as warnings for now until those can be fixed.
				'import/namespace': 'warn',
				'import/export': 'warn',
			},
			settings: {
				'import/parsers': {
					'@typescript-eslint/parser': [ '.ts', '.tsx' ],
				},
				'import/resolver': {
					typescript: {}, // this loads <rootdir>/tsconfig.json to eslint
				},
				'import/core-modules': [
					// We should lint these modules imports, but the types are way out of date.
					// To support us not inadvertently introducing new import errors this lint exists, but to avoid
					// having to fix hundreds of import errors for @wordpress packages we ignore them.
					'@wordpress/components',
					'@wordpress/element',
					'@wordpress/blocks',
					'@wordpress/notices',
				],
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
