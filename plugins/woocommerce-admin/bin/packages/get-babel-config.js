/**
 * External dependencies
 */
const { get, map } = require( 'lodash' );
const babel = require( '@babel/core' );

/**
 * External dependencies
 */
const { options: babelDefaultConfig } = babel.loadPartialConfig( {
	configFile: '@wordpress/babel-preset-default',
} );
const plugins = babelDefaultConfig.plugins;

const overrideOptions = ( target, targetName, options ) => {
	if ( get( target, [ 'file', 'request' ] ) === targetName ) {
		return [ targetName, Object.assign( {}, target.options, options ) ];
	}
	return target;
};

const babelConfigs = {
	main: Object.assign( {}, babelDefaultConfig, {
		plugins,
		presets: [
			[ '@babel/preset-typescript' ],
			...map( babelDefaultConfig.presets, ( preset ) =>
				overrideOptions( preset, '@babel/preset-env', {
					modules: 'commonjs',
					corejs: '3',
					useBuiltIns: 'usage',
				} )
			),
		],
	} ),
	module: Object.assign( {}, babelDefaultConfig, {
		plugins: map( plugins, ( plugin ) =>
			overrideOptions( plugin, '@babel/plugin-transform-runtime', {
				useESModules: true,
			} )
		),
		presets: [
			[ '@babel/preset-typescript' ],
			...map( babelDefaultConfig.presets, ( preset ) =>
				overrideOptions( preset, '@babel/preset-env', {
					modules: false,
					corejs: '3',
					useBuiltIns: 'usage',
				} )
			),
		],
	} ),
};

function getBabelConfig( environment ) {
	return babelConfigs[ environment ];
}

module.exports = getBabelConfig;
