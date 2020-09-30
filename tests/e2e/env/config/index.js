const jestConfig = require( './jest.config' );
const jestPuppeteerConfig = require( './jest-puppeteer.config' );
const { useE2EJestConfig, useE2EJestPuppeteerConfig } = require( './use-config' );

module.exports = {
	jestConfig,
	jestPuppeteerConfig,
	useE2EJestConfig,
	useE2EJestPuppeteerConfig,
};
