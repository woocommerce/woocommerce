/**
 * Internal dependencies
  */
const babelConfig = require( './babel.config' );
const esLintConfig = require( './.eslintrc.js' );
const allE2EConfig = require( './config' );
const allE2EUtils = require( './utils' );
/**
 * External dependencies
 */
const allPuppeteerUtils = require( '@automattic/puppeteer-utils' );

module.exports = {
	babelConfig,
	esLintConfig,
	...allE2EConfig,
	...allE2EUtils,
	...allPuppeteerUtils,
};
