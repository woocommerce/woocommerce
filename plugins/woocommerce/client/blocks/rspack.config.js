/* eslint-disable @typescript-eslint/no-require-imports -- Rspack loads this configuration as CommonJS. */
/**
 * Builds the configs from webpack.config.js with Rspack, all in one process.
 * Rspack already uses every CPU core, so the build is not split into shards.
 */
const webpackConfigs = require( './webpack.config.js' );
const { toRspackConfig } = require( './bin/rspack-adapter.js' );

module.exports = webpackConfigs.map( ( config ) =>
	toRspackConfig( config, config.name )
);
