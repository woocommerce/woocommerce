module.exports = {
	env: {
		'jest/globals': true,
	},
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
	settings: {
		'import/resolver': 'typescript',
		// List of modules that are externals in our webpack config.
		'import/core-modules': [ '@woocommerce/settings', 'lodash', 'react' ],
		react: {
			pragma: 'createElement',
		},
	},
	root: true,
	rules: {
		// temporary conversion to warnings until the below are all handled.
		'@wordpress/i18n-translator-comments': 'warn',
		'@wordpress/valid-sprintf': 'warn',
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
		'import/no-extraneous-dependencies': 'warn',
		'import/no-unresolved': 'warn',
		'jest/no-deprecated-functions': 'warn',
		'@wordpress/no-unsafe-wp-apis': 'warn',
		'jest/valid-title': 'warn',
		'@wordpress/no-global-active-element': 'warn',
		'no-unused-vars': [
			'error',
			{
				varsIgnorePattern: 'createElement',
			},
		],
		'react/react-in-jsx-scope': 'error',
	},
	overrides: [
		{
			files: [ '*.ts', '*.tsx' ],
			parser: '@typescript-eslint/parser',
			extends: [
				'plugin:@woocommerce/eslint-plugin/recommended',
				'plugin:@typescript-eslint/recommended',
			],
			rules: {
				camelcase: 'off',
				'import/no-unresolved': 'warn',
				'import/no-extraneous-dependencies': 'warn',
				'@typescript-eslint/no-explicit-any': 'error',
				'no-use-before-define': 'off',
				'@typescript-eslint/no-use-before-define': [ 'error' ],
				'jsdoc/require-param': 'off',
				// Making use of typescript no-shadow instead, fixes issues with enum.
				'no-shadow': 'off',
				'@typescript-eslint/no-shadow': [ 'error' ],
				'@typescript-eslint/no-empty-function': 'off',
			},
		},
		{
			files: [
				'client/**/*.js',
				'client/**/*.jsx',
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
