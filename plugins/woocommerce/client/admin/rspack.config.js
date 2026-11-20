/* eslint-disable @typescript-eslint/no-require-imports -- Rspack loads this configuration as CommonJS. */
/**
 * Builds the config from webpack.config.js with Rspack.
 */
const webpackConfig = require( './webpack.config.js' );
const { toRspackConfig } = require( '../blocks/bin/rspack-adapter.js' );

module.exports = toRspackConfig( webpackConfig, 'admin' );
