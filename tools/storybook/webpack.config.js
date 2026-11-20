/**
 * External dependencies
 */
const path = require( 'path' );
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );

const webpack = require( 'webpack' );

/**
 * Internal dependencies
 */
const {
	webpackConfig: styleConfig,
	WebpackRTLPlugin,
} = require( '@woocommerce/internal-build/style-build' );

// The admin build no longer uses webpack, so Storybook keeps the parts of
// the old admin webpack config that it needs to load admin code.
const ADMIN_DIR = path.resolve( __dirname, '../../plugins/woocommerce/client/admin' );
const SETTINGS_UI_DIR = path.resolve( __dirname, '../../packages/js/settings-ui' );
const packageDir = ( name, from ) =>
	path.dirname( require.resolve( `${ name }/package.json`, { paths: [ from ] } ) );
const dataViewsDir = packageDir( '@wordpress/dataviews', SETTINGS_UI_DIR );
const dataViewsUiDir = packageDir( '@wordpress/ui', dataViewsDir );
const dataViewsDependencyRoots = [
	...new Set(
		[
			dataViewsDir,
			packageDir( '@wordpress/components', dataViewsDir ),
			packageDir( '@wordpress/compose', dataViewsDir ),
			packageDir( '@wordpress/data', dataViewsDir ),
			dataViewsUiDir,
			packageDir( '@wordpress/theme', dataViewsUiDir ),
		].map( ( dir ) => path.resolve( dir, '../..' ) )
	),
];
const isProduction = process.env.NODE_ENV === 'production';

const wcAdminWebpackConfig = {
	module: {
		rules: [
			{
				include: path.resolve( SETTINGS_UI_DIR, 'src/dataform-runtime.ts' ),
				sideEffects: true,
			},
			{
				// DataViews' /wp build leaves its third-party imports external.
				// Resolve them from the package graph it was built against.
				include: require.resolve( '@wordpress/dataviews/wp', {
					paths: [ SETTINGS_UI_DIR ],
				} ),
				sideEffects: true,
				resolve: {
					modules: [ ...dataViewsDependencyRoots, 'node_modules' ],
				},
			},
			{
				test: /\.(t|j)sx?$/,
				parser: { amd: false },
				exclude: [
					/[\/\\]node_modules[\/\\]\.pnpm[\/\\]/,
					/[\/\\](changelog|bin|build|docs|test)[\/\\]/,
				],
				use: {
					loader: 'babel-loader',
					options: {
						configFile: false,
						sourceType: 'unambiguous',
						presets: [ '@wordpress/babel-preset-default' ],
						plugins: isProduction
							? [ require.resolve( 'babel-plugin-transform-react-remove-prop-types' ) ]
							: [],
					},
				},
			},
			{ test: /\.md$/, use: 'raw-loader' },
			{
				test: /@wordpress[\/\\]theme[\/\\].*\.css$/,
				sideEffects: true,
			},
			{
				test: /\.(png|jpe?g|gif|svg|eot|ttf|woff|woff2)$/,
				type: 'asset',
			},
			...styleConfig.rules,
		],
	},
	resolve: {
		alias: {
			'~': path.join( ADMIN_DIR, 'client' ),
			'react/jsx-dev-runtime': require.resolve( 'react/jsx-dev-runtime' ),
			'react/jsx-runtime': require.resolve( 'react/jsx-runtime' ),
		},
	},
	plugins: [
		...styleConfig.plugins,
		new webpack.DefinePlugin( {
			__i18n_text_domain__: JSON.stringify( 'woocommerce' ),
		} ),
	],
};

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
