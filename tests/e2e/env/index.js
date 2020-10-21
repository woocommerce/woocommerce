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

const {
	clickAndWaitForNewPage,
	getAccountCredentials,
	isEventuallyPresent,
	isEventuallyVisible,
	logDebugLog,
	logHTML,
	waitAndClick,
	waitAndType,
	waitForSelector,
	scrollIntoView,
	Page
} = require( '@automattic/puppeteer-utils' );

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
	clickAndWaitForNewPage,
	getAccountCredentials,
	isEventuallyPresent,
	isEventuallyVisible,
	logDebugLog,
	logHTML,
	waitAndClick,
	waitAndType,
	waitForSelector,
	scrollIntoView,
	Page,
};
