const jestConfig = require( './jest.config.js' );
const jestPuppeteerConfig = require( './jest-puppeteer.config.js' );

const useE2EJestConfig = function( customConfig ) {
	const combinedConfig = {
		...jestConfig,
		...customConfig,
	};

	return combinedConfig;
};

const useE2EJestPuppeteerConfig = function( customPuppeteerConfig ) {
	let combinedPuppeteerConfig = {
		...jestPuppeteerConfig,
		...customPuppeteerConfig,
	};
	// Only need to be merged if both exist.
	if ( jestPuppeteerConfig.launch && customPuppeteerConfig.launch ) {
		combinedPuppeteerConfig.launch = {
			...jestPuppeteerConfig.launch,
			...customPuppeteerConfig.launch,
		};
	}
	return combinedPuppeteerConfig;
};

module.exports = { useE2EJestConfig, useE2EJestPuppeteerConfig };
