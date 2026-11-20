/* eslint-disable @typescript-eslint/no-require-imports -- Rspack loads this configuration as CommonJS. */
/**
 * Turns one of the webpack config objects into an Rspack config. Every
 * difference between the two bundlers is handled here, so the webpack configs
 * stay as they are and the webpack build keeps working.
 */
const path = require( 'path' );
const { rspack } = require( '@rspack/core' );
const TerserPlugin = require( 'terser-webpack-plugin' );
const { swcLoader } = require( '@woocommerce/internal-build/swc-loader' );

// Rspack's built-in swc-loader, with the options the webpack build passes to
// swc-loader. The configs that still use babel-loader get the same options.
const builtinSwcUse = ( info ) =>
	swcLoader( info ).map( ( { options: { swcrc, ...options } } ) => ( {
		loader: 'builtin:swc-loader',
		options,
	} ) );

const loaderOf = ( use ) => ( typeof use === 'string' ? use : use?.loader );
const isBabel = ( use ) => /babel-loader/.test( loaderOf( use ) || '' );
const isMiniCssLoader = ( use ) =>
	/mini-css-extract-plugin/.test( loaderOf( use ) || '' );

const convertUse = ( use ) => {
	if ( Array.isArray( use ) ) {
		return use.map( convertUse );
	}
	if ( isMiniCssLoader( use ) ) {
		return rspack.CssExtractRspackPlugin.loader;
	}
	return use;
};

const convertRule = ( rule ) => {
	if ( ! rule || ! rule.use ) {
		return rule;
	}
	if ( rule.use === swcLoader || [ rule.use ].flat().every( isBabel ) ) {
		return { ...rule, use: builtinSwcUse };
	}
	return { ...rule, use: convertUse( rule.use ) };
};

// Lazy CSS chunks need the `-rtl` file on RTL pages. With webpack the RTL
// plugin taps a mini-css-extract-plugin hook for this. CssExtractRspackPlugin
// has no such hook, so the same swap is done in its `insert` callback, which
// runs in the browser.
const insertRtlAwareLink = function ( linkTag ) {
	if ( document.dir === 'rtl' ) {
		const href = linkTag.getAttribute( 'href' );
		linkTag.setAttribute( 'data-href', href );
		linkTag.href = href.replace( /\.css(?:$|\?)/, '-rtl$&' );
	}
	document.head.appendChild( linkTag );
};

const convertPlugin = ( plugin, { hasRtlPlugin } ) => {
	switch ( plugin?.constructor?.name ) {
		case 'MiniCssExtractPlugin':
			return new rspack.CssExtractRspackPlugin( {
				filename: plugin.options.filename,
				chunkFilename: plugin.options.chunkFilename,
				...( hasRtlPlugin ? { insert: insertRtlAwareLink } : {} ),
			} );
		case 'DefinePlugin':
			return new rspack.DefinePlugin( plugin.definitions );
		case 'LimitChunkCountPlugin':
			return new rspack.optimize.LimitChunkCountPlugin( plugin.options );
		case 'CopyPlugin':
			// copy-webpack-plugin hangs in processAssets under Rspack.
			return new rspack.CopyRspackPlugin( {
				patterns: plugin.patterns,
			} );
		case 'ProgressBarPlugin':
			// Wraps webpack's own ProgressPlugin, which cannot attach to Rspack.
			return null;
		case 'CustomTemplatedPathPlugin':
			// Needs `compilation.hooks.assetPath`, which Rspack does not have.
			// The `[modulename]` tag is resolved per entry instead.
			return null;
		default:
			return plugin;
	}
};

// Webpack's filesystem cache stores Terser results, so a warm webpack build
// skips minification. Rspack's persistent cache does not cover JavaScript
// plugins, so Terser would run in full on every build. This wrapper stores
// each minified file on disk, keyed by its input and options. It runs inside
// Terser's worker threads, so it must not use anything from this file's scope.
const cachedTerserMinify = async (
	input,
	sourceMap,
	minimizerOptions,
	extractComments
) => {
	/* eslint-disable @typescript-eslint/no-shadow -- Runs in a worker without this module's scope. */
	const crypto = require( 'crypto' );
	const fs = require( 'fs' );
	const path = require( 'path' );
	/* eslint-enable @typescript-eslint/no-shadow */
	const { __wcCache: cache, ...terserOptions } = minimizerOptions;
	const stringify = ( value ) =>
		JSON.stringify( value, ( key, item ) =>
			item instanceof RegExp || typeof item === 'function'
				? String( item )
				: item
		);
	const key = crypto
		.createHash( 'sha256' )
		.update(
			stringify( [
				cache.version,
				input,
				Boolean( sourceMap ),
				terserOptions,
				extractComments,
			] )
		)
		.digest( 'hex' );
	const file = path.join( cache.directory, key + '.json' );
	try {
		return JSON.parse( fs.readFileSync( file, 'utf8' ) );
	} catch {
		// Not cached yet.
	}
	const { terserMinify } = require( cache.utilsPath );
	const result = await terserMinify(
		input,
		sourceMap,
		terserOptions,
		extractComments
	);
	if ( ! result.errors?.length && ! sourceMap ) {
		fs.mkdirSync( cache.directory, { recursive: true } );
		const temporaryFile = file + '.' + process.pid + '.tmp';
		fs.writeFileSync( temporaryFile, JSON.stringify( result ) );
		fs.renameSync( temporaryFile, file );
	}
	return result;
};

const terserCache = ( cacheEnabled ) => {
	if ( ! cacheEnabled ) {
		return null;
	}
	const pluginDirectory = path.dirname(
		require.resolve( 'terser-webpack-plugin' )
	);
	return {
		directory: path.resolve(
			process.cwd(),
			'node_modules/.cache/rspack-terser'
		),
		utilsPath: path.join( pluginDirectory, 'utils.js' ),
		version: [
			require( 'terser/package.json' ).version,
			require( 'terser-webpack-plugin/package.json' ).version,
		].join( '+' ),
	};
};

// Terser stays the minifier. Rspack's SWC minifier cannot keep only the
// `translators:` comments, and `wp i18n make-pot` fails to parse some of its
// output, so translatable strings would be lost.
const convertMinimizer = ( minimizer, { cacheEnabled } ) => {
	const cache = terserCache( cacheEnabled );
	if ( minimizer?.constructor?.name !== 'TerserPlugin' || ! cache ) {
		return minimizer;
	}
	return new TerserPlugin( {
		parallel: minimizer.options.parallel,
		extractComments: minimizer.options.extractComments,
		minify: cachedTerserMinify,
		terserOptions: {
			...minimizer.options.minimizer?.options,
			__wcCache: cache,
		},
	} );
};

const convertCache = ( cache, name ) => {
	if ( cache?.type === 'memory' ) {
		return { type: 'memory' };
	}
	if ( ! cache || cache.type !== 'filesystem' ) {
		return false;
	}
	return {
		type: 'persistent',
		buildDependencies: [
			...( cache.buildDependencies?.config || [] ),
			__filename,
		],
		storage: {
			type: 'filesystem',
			directory: path.join(
				path.dirname( cache.cacheDirectory ),
				`rspack-${ name }`
			),
		},
	};
};

const camelCaseDash = ( name ) =>
	name.replace( /-([a-z])/g, ( match, letter ) => letter.toUpperCase() );

// `library: [ 'wc', '[modulename]' ]` is a custom path tag in the admin
// config. Give each entry its own library name instead.
const convertModulenameLibrary = ( config ) => {
	const library = config.output?.library;
	const names = library?.name;
	if ( ! Array.isArray( names ) || ! names.includes( '[modulename]' ) ) {
		return config;
	}
	const entry = Object.fromEntries(
		Object.entries( config.entry ).map( ( [ name, value ] ) => [
			name,
			{
				import: value,
				library: {
					...library,
					name: names.map( ( part ) =>
						part === '[modulename]' ? camelCaseDash( name ) : part
					),
				},
			},
		] )
	);
	const { library: removed, ...output } = config.output;
	return { ...config, entry, output };
};

/**
 * @param {Object} webpackConfig One webpack config object.
 * @param {string} name          Name for the persistent cache folder.
 * @return {Object} The matching Rspack config.
 */
const toRspackConfig = ( webpackConfig, name ) => {
	let config = { ...webpackConfig };
	const output = { ...config.output };

	// Rspack 2 removed `output.libraryTarget`.
	if ( output.libraryTarget ) {
		output.library = {
			name: output.library,
			type: output.libraryTarget,
		};
		delete output.libraryTarget;
	}
	// Rspack 2 names the chunk loading global `rspackChunk…` by default.
	// Keep the webpack name so the built files stay the same.
	if ( ! output.chunkLoadingGlobal ) {
		output.chunkLoadingGlobal = `webpackChunk${ output.uniqueName || '' }`;
	}
	// Webpack's defaults. They keep `version` strings and hashed file names
	// in the same format (Rspack defaults to xxhash64 and 16 characters).
	output.hashFunction = output.hashFunction || 'md4';
	output.hashDigestLength = output.hashDigestLength || 20;
	config.output = output;

	// Rspack 2 removed `experiments.outputModule`; `output.module` is enough.
	if ( config.experiments ) {
		const { outputModule, ...experiments } = config.experiments;
		config.experiments = experiments;
	}

	const cacheEnabled = config.cache?.type === 'filesystem';
	config.cache = convertCache( config.cache, name );

	config.module = {
		...config.module,
		// Rspack 2 fails the build on a re-export it cannot find, such as a
		// TypeScript type re-exported without `export type`. Webpack only
		// warns by default, and trunk hides warnings in its stats.
		parser: config.module?.parser || {
			javascript: { exportsPresence: 'warn' },
		},
		rules: ( config.module?.rules || [] ).map( convertRule ),
	};

	const hasRtlPlugin = ( config.plugins || [] ).some(
		( plugin ) => plugin?.constructor?.name === 'WebpackRTLPlugin'
	);
	config.plugins = ( config.plugins || [] )
		.map( ( plugin ) => convertPlugin( plugin, { hasRtlPlugin } ) )
		.filter( Boolean );

	// Without an explicit minimizer, webpack runs Terser with these options and
	// writes `*.LICENSE.txt` files. Rspack would run its SWC minifier and also
	// LightningCSS over the CSS that cssnano already minified.
	const minimizer = config.optimization?.minimizer || [
		new TerserPlugin( { terserOptions: { compress: { passes: 2 } } } ),
	];
	config.optimization = {
		...config.optimization,
		minimizer: minimizer.map( ( item ) =>
			convertMinimizer( item, { cacheEnabled } )
		),
	};

	config = convertModulenameLibrary( config );

	return config;
};

module.exports = { toRspackConfig };
