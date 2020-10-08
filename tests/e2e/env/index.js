/**
 * Internal dependencies
  */
const babelConfig = require( './babel.config' );
const esLintConfig = require( './.eslintrc.js' );
const {
	jestConfig,
	jestPuppeteerConfig,
	useE2EBabelConfig,
	useE2EEsLintConfig,
	useE2EJestConfig,
	useE2EJestPuppeteerConfig,
} = require( './config' );
const { getAppRoot, getTestConfig } = require( './utils' );
const webpackAlias = require( './webpack-alias' );

module.exports = {
	babelConfig,
	esLintConfig,
	jestConfig,
	jestPuppeteerConfig,
	useE2EBabelConfig,
	useE2EEsLintConfig,
	useE2EJestConfig,
	useE2EJestPuppeteerConfig,
	getAppRoot,
	getTestConfig,
	webpackAlias,
};
