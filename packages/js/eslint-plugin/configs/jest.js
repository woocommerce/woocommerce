/**
 * External dependencies
 */
const jestPlugin = require( 'eslint-plugin-jest' );

/*
 * @wordpress/eslint-plugin 28 moved its unit test config from Jest to Vitest.
 * WooCommerce still uses Jest, so this keeps the previous Jest config.
 */
module.exports = [
	jestPlugin.configs[ 'flat/recommended' ],
	{
		rules: {
			'jest/expect-expect': [
				'error',
				{ assertFunctionNames: [ 'expect', 'measurePerformance' ] },
			],
		},
	},
];
