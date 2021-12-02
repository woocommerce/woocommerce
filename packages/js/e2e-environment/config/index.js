/**
 * Internal dependencies
 */
const jestConfig = require( './jest.config' );
const jestPuppeteerConfig = require( './jest-puppeteer.config' );
const setupJestObject = require('./jest-object.config');
const {
	useE2EBabelConfig,
	useE2EEsLintConfig,
	useE2EJestConfig,
	useE2EJestPuppeteerConfig,
} = require( './use-config' );

module.exports = {
	jestConfig,
	setupJestObject,
	jestPuppeteerConfig,
	useE2EBabelConfig,
	useE2EEsLintConfig,
	useE2EJestConfig,
	useE2EJestPuppeteerConfig,
};
