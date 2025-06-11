const jestConfig = require( './jest.config.js' );
const jestPuppeteerConfig = require( './jest-puppeteer.config.js' );
const babelConfig = require( '../babel.config' );
const esLintConfig = require( '../.eslintrc.js' );

const useE2EBabelConfig = function( customBabelConfig ) {
	const combinedBabelConfig = {
		...babelConfig,
		...customBabelConfig,
	};

	// These only need to be merged if both exist.
	if ( babelConfig.plugins && customBabelConfig.plugins ) {
		combinedBabelConfig.plugins = [
			...babelConfig.plugins,
			...customBabelConfig.plugins,
		];
	}
	if ( babelConfig.presets && customBabelConfig.presets ) {
		combinedBabelConfig.presets = [
			...babelConfig.presets,
			...customBabelConfig.presets,
		];
	}

	return combinedBabelConfig;
};

const useE2EEsLintConfig = function( customEsLintConfig ) {
	let combinedEsLintConfig = {
		...esLintConfig,
		...customEsLintConfig,
	};

	// These only need to be merged if both exist.
	if ( esLintConfig.extends && customEsLintConfig.extends ) {
		combinedEsLintConfig.extends = [
			...esLintConfig.extends,
			...customEsLintConfig.extends,
		];
	}
	if ( esLintConfig.env && customEsLintConfig.env ) {
		combinedEsLintConfig.env = {
			...esLintConfig.env,
			...customEsLintConfig.env,
		};
	}
	if ( esLintConfig.globals && customEsLintConfig.globals ) {
		combinedEsLintConfig.globals = {
			...esLintConfig.globals,
			...customEsLintConfig.globals,
		};
	}
	if ( esLintConfig.plugins && customEsLintConfig.plugins ) {
		combinedEsLintConfig.plugins = [
			...esLintConfig.plugins,
			...customEsLintConfig.plugins,
		];
	}
	return combinedEsLintConfig;
};

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

module.exports = {
	useE2EBabelConfig,
	useE2EEsLintConfig,
	useE2EJestConfig,
	useE2EJestPuppeteerConfig,
};
