/**
 * External dependencies
 */
const path = require( 'path' );
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );

/**
 * External dependencies
 */
const wcAdminWebpackConfig = require( '../../plugins/woocommerce/client/admin/webpack.config.js' );
const {
	WebpackRTLPlugin,
} = require( '@woocommerce/internal-build/style-build' );

module.exports = ( storybookConfig ) => {
	storybookConfig.module.rules = [
		...storybookConfig.module.rules,
		...wcAdminWebpackConfig.module.rules,
	];

	// Copy (don't share) the admin alias object since we mutate it below.
	storybookConfig.resolve.alias = { ...wcAdminWebpackConfig.resolve.alias };

	// Bundle every `@woocommerce/*` package from source, mirroring the admin
	// webpack config. Each package declares a `"wc-source"` conditional export
	// resolving to its `./src/index.ts`, so activating the condition picks up
	// all current and future packages without a hardcoded alias list.
	storybookConfig.resolve.conditionNames = [ 'wc-source', '...' ];

	storybookConfig.resolve.alias[ '@woocommerce/settings' ] = path.resolve(
		__dirname,
		'./setting.mock.js'
	);

	// We need to use react 18 for the storybook since some dependencies are not compatible with react 17
	// Once we upgrade react to 18 in repo, we can remove this alias
	storybookConfig.resolve.alias.react = path.resolve(
		__dirname,
		'./node_modules/react'
	);
	storybookConfig.resolve.alias[ 'react-dom' ] = path.resolve(
		__dirname,
		'./node_modules/react-dom'
	);
	storybookConfig.resolve.alias[ '@storybook/react-dom-shim' ] =
		'@storybook/react-dom-shim/dist/react-18';

	storybookConfig.resolve.modules = [
		path.join( __dirname, '../../plugins/woocommerce/client/admin/client' ),
		'node_modules',
	];

	// When USE_RTL_STYLE is set (the `storybook-rtl` script), swap the
	// inherited RTL plugin for an in-place instance so the compiled CSS is
	// rewritten to RTL rather than emitted as an unused `-rtl.css` sibling.
	// This keeps RTL preview on the existing rtlcss toolchain now that the
	// stylesheets are bundled from source instead of copied as pre-built files.
	const useRtl = process.env.USE_RTL_STYLE === 'true';
	const inheritedPlugins = wcAdminWebpackConfig.plugins.map( ( plugin ) =>
		useRtl && plugin instanceof WebpackRTLPlugin
			? new WebpackRTLPlugin( { inPlace: true } )
			: plugin
	);

	storybookConfig.plugins.push(
		...inheritedPlugins,
		new CopyWebpackPlugin( {
			patterns: [
				{
					from: path.resolve( __dirname, 'wordpress/css' ),
					to: 'wordpress/css/[name][ext]',
				},
				{
					/*
					 * Resolve the package root via its exported `package.json`
					 * and join the CSS path manually. `@wordpress/components`
					 * still maps `build-style` as a deprecated trailing-slash
					 * folder export, which Node 24 no longer honors, so a direct
					 * `require.resolve` of the stylesheet throws
					 * ERR_PACKAGE_PATH_NOT_EXPORTED.
					 */
					from: path.join(
						path.dirname(
							require.resolve(
								'@wordpress/components/package.json'
							)
						),
						'build-style/style.css'
					),
					to: 'wordpress/css/components.css',
				},
			],
		} )
	);

	storybookConfig.resolve.fallback = {
		...storybookConfig.resolve.fallback,
		// Ignore fs to fix resolve 'fs' error for @automattic/calypso-config
		fs: false,
	};

	return storybookConfig;
};
