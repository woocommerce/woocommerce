/** @format */

module.exports = {
	env: {
		browser: true,
		es6: true,
		node: true,
	},
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
	globals: {
		wp: true,
		wpApiSettings: true,
		wcSettings: true,
		es6: true,
	},
	rules: {
		camelcase: 0,
		indent: 0,
		'max-len': [ 2, { code: 140 } ],
		'no-console': 1,
	},
	parser: 'babel-eslint',
	parserOptions: {
		ecmaVersion: 8,
		ecmaFeatures: {
			modules: true,
			experimentalObjectRestSpread: true,
			jsx: true,
		},
	},
};
