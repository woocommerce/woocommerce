// Internal dependencies
const babelConfig = require( './babel.config' );
const esLintConfig = require( './.eslintrc.js' );
const {
	jestConfig,
	jestPuppeteerConfig,
	useE2EJestConfig,
	useE2EJestPuppeteerConfig,
} = require( './config' );
const webpackAlias = require( './webpack-alias' );

module.exports = {
	babelConfig,
	esLintConfig,
	jestConfig,
	jestPuppeteerConfig,
	useE2EJestConfig,
	useE2EJestPuppeteerConfig,
	webpackAlias,
};
