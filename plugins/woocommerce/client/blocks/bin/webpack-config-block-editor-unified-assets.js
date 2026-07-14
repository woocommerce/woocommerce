/**
 * External dependencies
 */
const path = require( 'path' );
const { omit } = require( 'lodash' );
const glob = require( 'glob' );
const ProgressBarPlugin = require( 'progress-bar-webpack-plugin' );

/**
 * Internal dependencies
 */
const { getEntryConfig } = require( './webpack-entries' );
const { editorStyleEntries } = require( './webpack-interactivity-entries' );
const {
	NODE_ENV,
	getProgressBarPluginConfig,
	getResolve,
	requestToExternal,
	requestToHandle,
} = require( './webpack-helpers' );
const { getSharedPlugins, getStylingConfig } = require( './webpack-configs' );
const { sharedOptimizationConfig } = require( './webpack-shared-config' );

const ROOT_DIR = path.resolve( __dirname, '../../../../../' );
// Blocks' webpack writes directly to the WooCommerce plugin's
// `assets/client/blocks/` so PHP can enqueue files from their final location
// without an intermediate rsync step.
const BUILD_DIR = path.resolve( __dirname, '../../../assets/client/blocks' );
const BABEL_CACHE_DIR = path.join(
	ROOT_DIR,
	'node_modules/.cache/babel-loader'
);
const isProduction = NODE_ENV === 'production';
const UNIFIED_EDITOR_STYLE_HANDLE = 'wc-block-library-style';

const editorExternalPackages = [
	'@woocommerce/block-data',
	'@woocommerce/blocks-checkout',
	'@woocommerce/blocks-checkout-events',
	'@woocommerce/blocks-components',
	'@woocommerce/blocks-registry',
	'@woocommerce/data',
	'@woocommerce/entities',
	'@woocommerce/price-format',
	'@woocommerce/shared-context',
	'@woocommerce/shared-hocs',
];

const shouldBundleWooPackage = ( request ) =>
	request.startsWith( '@woocommerce/' ) &&
	! editorExternalPackages.includes( request );

const requestToUnifiedEditorExternal = ( request ) => {
	if ( shouldBundleWooPackage( request ) ) {
		return false;
	}

	return requestToExternal( request );
};

const requestToUnifiedEditorHandle = ( request ) => {
	if ( shouldBundleWooPackage( request ) ) {
		return false;
	}

	return requestToHandle( request );
};

const getUnifiedEditorPackageAliases = () => ( {
	'@woocommerce/block-data': path.resolve( __dirname, `../assets/js/data` ),
	'@woocommerce/blocks-checkout': path.resolve(
		__dirname,
		`../packages/checkout`
	),
	'@woocommerce/blocks-checkout-events': path.resolve(
		__dirname,
		`../assets/js/events`
	),
	'@woocommerce/blocks-components': path.resolve(
		__dirname,
		`../packages/components`
	),
	'@woocommerce/blocks-registry': path.resolve(
		__dirname,
		`../assets/js/blocks-registry`
	),
	'@woocommerce/data': path.resolve(
		__dirname,
		`../../../../../packages/js/data/src/index.ts`
	),
	'@woocommerce/price-format': path.resolve(
		__dirname,
		`../packages/prices`
	),
	'@woocommerce/sanitize': path.resolve(
		__dirname,
		`../../../../../packages/js/sanitize/src/index.ts`
	),
	'@woocommerce/settings': path.resolve(
		__dirname,
		`../assets/js/settings/shared`
	),
	'@woocommerce/shared-context': path.resolve(
		__dirname,
		`../assets/js/shared/context/`
	),
	'@woocommerce/shared-hocs': path.resolve(
		__dirname,
		`../assets/js/shared/hocs/`
	),
} );

const addEditorBundleResourceQuery = ( filePath ) =>
	`${ filePath }?editor-bundle`;

const getUnifiedEditorStyleEntries = ( exclude = [] ) =>
	omit(
		{
			'wc-block-library-style-source': [
				'./assets/css/style.scss',
				'./assets/css/editor.scss',
				...glob.sync( './assets/js/**/{style,editor}.scss', {
					dotRelative: true,
				} ),
				...glob.sync( './packages/**/style.scss', {
					dotRelative: true,
					ignore: './packages/**/stories/**',
				} ),
			].map( addEditorBundleResourceQuery ),
			'interactivity-editor-styles': Object.values( editorStyleEntries )
				.flat()
				.map( addEditorBundleResourceQuery ),
		},
		exclude
	);

/**
 * Build config for unified Blocks editor scripts.
 *
 * @param {Object} options Build options.
 */
const getUnifiedMainConfig = ( options = {} ) => {
	const { alias, resolvePlugins = [] } = options;
	const resolve = getResolve( {
		alias: {
			...getUnifiedEditorPackageAliases(),
			...alias,
		},
		resolvePlugins,
	} );

	return {
		entry: omit(
			{
				'wc-block-library': Object.values(
					getEntryConfig( 'main' )
				).flat(),
			},
			options.exclude || []
		),
		output: {
			devtoolNamespace: 'wc',
			path: BUILD_DIR,
			// Keep the filename stable for WordPress translations while using
			// the query string to invalidate cached chunks after a build.
			chunkFilename: `wc-block-library-[name].js?ver=[contenthash]`,
			filename: `[name].js`,
			library: [ 'wc', 'blocks', '[name]' ],
			libraryTarget: 'this',
			uniqueName: 'webpackWcBlocksUnifiedMainJsonp',
		},
		module: {
			rules: [
				{
					test: /\.(j|t)sx?$/,
					exclude: [ /[\/\\](node_modules|build|docs|vendor)[\/\\]/ ],
					use: {
						loader: 'babel-loader',
						options: {
							presets: [ '@wordpress/babel-preset-default' ],
							plugins: [
								isProduction
									? require.resolve(
											'babel-plugin-transform-react-remove-prop-types'
									  )
									: false,
							].filter( Boolean ),
							cacheDirectory: BABEL_CACHE_DIR,
							cacheCompression: false,
						},
					},
				},
				{
					test: /\.s[c|a]ss$/,
					use: {
						loader: 'ignore-loader',
					},
				},
			],
		},
		optimization: {
			...sharedOptimizationConfig,
			splitChunks: false,
		},
		plugins: [
			...getSharedPlugins( {
				bundleAnalyzerReportTitle: 'Unified editor',
				dependencyRequestToExternal: requestToUnifiedEditorExternal,
				dependencyRequestToHandle: requestToUnifiedEditorHandle,
			} ),
			new ProgressBarPlugin(
				getProgressBarPluginConfig( 'Unified editor' )
			),
		],
		resolve: {
			...resolve,
			extensions: [ '.js', '.jsx', '.ts', '.tsx' ],
		},
	};
};

/**
 * Build config for unified Blocks editor styles.
 *
 * @param {Object} options Build options.
 */
const getUnifiedStylingConfig = ( options = {} ) => {
	const stylingConfig = getStylingConfig( {
		...options,
		configName: 'Unified styles',
	} );

	return {
		...stylingConfig,
		entry: getUnifiedEditorStyleEntries( options.exclude || [] ),
		output: {
			...stylingConfig.output,
			uniqueName: 'webpackWcBlocksUnifiedStylingJsonp',
		},
		optimization: {
			...stylingConfig.optimization,
			splitChunks: {
				...stylingConfig.optimization.splitChunks,
				cacheGroups: {
					...stylingConfig.optimization.splitChunks.cacheGroups,
					editorStyle: {
						test: ( module = {} ) => {
							if ( ! module.type.includes( 'css' ) ) {
								return false;
							}

							const moduleIdentifier =
								typeof module.identifier === 'function'
									? module.identifier()
									: '';

							return moduleIdentifier.includes(
								'?editor-bundle'
							);
						},
						name: UNIFIED_EDITOR_STYLE_HANDLE,
						chunks: 'all',
						enforce: true,
						priority: 10,
					},
				},
			},
		},
	};
};

module.exports = {
	getUnifiedMainConfig,
	getUnifiedStylingConfig,
};
