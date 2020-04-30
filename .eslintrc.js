/** @format */
const baseConfig = require( '@woocommerce/e2e-environment' ).esLintConfig;

module.exports = {
	...baseConfig,
	root: true,
	env: {
		...baseConfig.env,
		browser: true,
		es6: true,
		node: true
	},
	globals: {
		...baseConfig.globals,
		wp: true,
		wpApiSettings: true,
		wcSettings: true,
		es6: true
	},
	rules: {
		camelcase: 0,
		indent: 0,
		'max-len': [ 2, { 'code': 140 } ],
		'no-console': 1
	},
	parser: 'babel-eslint',
	parserOptions: {
		ecmaVersion: 8,
		ecmaFeatures: {
			modules: true,
			experimentalObjectRestSpread: true,
			jsx: true
		}
	},
};
