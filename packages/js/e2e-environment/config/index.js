/**
 * Internal dependencies
 */
const jestConfig = require( './jest.config' );
const jestPuppeteerConfig = require( './jest-puppeteer.config' );
const jestobjectConfig = require('./jest-object.config');
const {
	useE2EBabelConfig,
	useE2EEsLintConfig,
	useE2EJestConfig,
	useE2EJestPuppeteerConfig,
} = require( './use-config' );

module.exports = {
	jestConfig,
	...jestobjectConfig,
	jestPuppeteerConfig,
	useE2EBabelConfig,
	useE2EEsLintConfig,
	useE2EJestConfig,
	useE2EJestPuppeteerConfig,
};
