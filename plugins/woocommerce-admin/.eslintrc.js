module.exports = {
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
	rules: {
		// temporary conversion to warnings until the below are all handled.
		'@wordpress/i18n-translator-comments': 'warn',
		'@wordpress/valid-sprintf': 'warn',
		'jsdoc/check-tag-names': [
			'error',
			{ definedTags: [ 'jest-environment' ] },
		],
		'import/no-extraneous-dependencies': 'warn',
		'import/no-unresolved': 'warn',
		'jest/no-deprecated-functions': 'warn',
		'@wordpress/no-unsafe-wp-apis': 'warn',
		'jest/valid-title': 'warn',
		'@wordpress/no-global-active-element': 'warn',
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
				'@typescript-eslint/no-explicit-any': 'error',
				'no-use-before-define': 'off',
				'@typescript-eslint/no-use-before-define': [ 'error' ],
				'jsdoc/require-param': 'off',
			},
		},
	],
};
