module.exports = {
	extends: [
		'plugin:react-hooks/recommended',
		require.resolve( './custom.js' ),
		'plugin:@wordpress/eslint-plugin/recommended',
	],
	parser: '@typescript-eslint/parser',
	globals: {
		wcSettings: 'readonly',
		'jest/globals': true,
		jest: true,
	},
	plugins: [ '@wordpress' ],
	rules: {
		radix: 'error',
		yoda: [ 'error', 'never' ],
		// temporary conversion to warnings until the below are all handled.
		'@wordpress/i18n-translator-comments': 'warn',
		'@wordpress/valid-sprintf': 'warn',
		'@wordpress/no-unsafe-wp-apis': 'warn',
		'@wordpress/no-global-active-element': 'warn',
		'import/no-extraneous-dependencies': 'warn',
		'import/no-unresolved': 'warn',
		'jest/no-deprecated-functions': 'warn',
		'jest/valid-title': 'warn',
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
		'react/react-in-jsx-scope': 'error',
	},
	settings: {
		'import/resolver': 'typescript',
		// List of modules that are externals in our webpack config.
		'import/core-modules': [ '@woocommerce/settings', 'lodash', 'react' ],
		react: {
			pragma: 'createElement',
		},
	},
	overrides: [
		{
			files: [ '*.ts', '*.tsx' ],
			extends: [ 'plugin:@typescript-eslint/recommended' ],
			rules: {
				'@typescript-eslint/no-explicit-any': 'error',
				'@typescript-eslint/no-use-before-define': [ 'error' ],
				'@typescript-eslint/no-shadow': [ 'error' ],
				'@typescript-eslint/no-empty-function': 'off',
				camelcase: 'off',
				'no-use-before-define': 'off',
				'jsdoc/require-param': 'off',
				// Making use of typescript no-shadow instead, fixes issues with enum.
				'no-shadow': 'off',
			},
		},
		{
			files: [
				'**/stories/*.js',
				'**/stories/*.jsx',
				'**/docs/example.js',
			],
			rules: {
				'react/react-in-jsx-scope': 'off',
			},
		},
	],
};
