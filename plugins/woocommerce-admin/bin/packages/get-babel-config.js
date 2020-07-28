/**
 * External dependencies
 */
const { get, map } = require( 'lodash' );
const babel = require( '@babel/core' );

/**
 * WordPress dependencies
 */
const { options: babelDefaultConfig } = babel.loadPartialConfig( {
	configFile: '@wordpress/babel-preset-default',
} );
const plugins = babelDefaultConfig.plugins;

const overrideOptions = ( target, targetName, options ) => {
	if ( get( target, [ 'file', 'request' ] ) === targetName ) {
		return [ targetName, Object.assign(
			{},
			target.options,
			options
		) ];
	}
	return target;
};

const babelConfigs = {
	main: Object.assign(
		{},
		babelDefaultConfig,
		{
			plugins,
			presets: map(
				babelDefaultConfig.presets,
				( preset ) => overrideOptions( preset, '@babel/preset-env', {
					modules: 'commonjs',
				} )
			),
		}
	),
	module: Object.assign(
		{},
		babelDefaultConfig,
		{
			plugins: map(
				plugins,
				( plugin ) => overrideOptions( plugin, '@babel/plugin-transform-runtime', {
					useESModules: true,
				} )
			),
			presets: map(
				babelDefaultConfig.presets,
				( preset ) => overrideOptions( preset, '@babel/preset-env', {
					modules: false,
				} )
			),
		}
	),
};

function getBabelConfig( environment ) {
	return babelConfigs[ environment ];
}

module.exports = getBabelConfig;
