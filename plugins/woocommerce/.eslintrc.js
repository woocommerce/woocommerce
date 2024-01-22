/** @format */

module.exports = {
	env: {
		browser: true,
		es6: true,
		node: true,
	},
	globals: {
		wp: true,
		wpApiSettings: true,
		wcSettings: true,
		es6: true,
	},
	rules: {
		camelcase: 0,
		indent: 0,
		'no-console': 1,
	},
	parser: '@babel/eslint-parser',
	parserOptions: {
		ecmaVersion: 8,
		ecmaFeatures: {
			modules: true,
			experimentalObjectRestSpread: true,
			jsx: true,
		},
	},
	overrides: [
		{
			files: [ 'tests/e2e/**/*.spec.js' ],
			rules: {
				'jest/no-test-callback': 'off',
				'@wordpress/no-unsafe-wp-apis': 'off',
				'import/no-extraneous-dependencies': 'off',
				'import/no-unresolved': 'off',
			},
		},
	],
};
