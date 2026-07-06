/* eslint-disable no-console */
/**
 * External dependencies
 */
const path = require( 'path' );
const chalk = require( 'chalk' );
const NODE_ENV = process.env.NODE_ENV || 'development';
const CHECK_CIRCULAR_DEPS = process.env.CHECK_CIRCULAR_DEPS || false;
const ASSET_CHECK = process.env.ASSET_CHECK === 'true';
const SHARED_EDITOR_STYLE_HANDLE = 'wc-block-library-style';

// See also @woocommerce/dependency-extraction-webpack-plugin/assets/packages. It will backfill any missing
// mapping here and any duplicates are because of switched between Woo and WordPress versions of the plugin.
// As of 2026 it's Woo version to address pnpm peer dependencies related issues to support filesystem cache.
const wcDepMap = {
	'@woocommerce/tracks': false, // Bundle; do not externalize
	'@woocommerce/blocks-registry': [ 'wc', 'wcBlocksRegistry' ],
	'@woocommerce/blocks-checkout-events': [ 'wc', 'blocksCheckoutEvents' ],
	'@woocommerce/settings': [ 'wc', 'wcSettings' ],
	'@woocommerce/block-data': [ 'wc', 'wcBlocksData' ],
	'@woocommerce/data': [ 'wc', 'data' ],
	'@woocommerce/shared-context': [ 'wc', 'wcBlocksSharedContext' ],
	'@woocommerce/shared-hocs': [ 'wc', 'wcBlocksSharedHocs' ],
	'@woocommerce/price-format': [ 'wc', 'priceFormat' ],
	'@woocommerce/blocks-checkout': [ 'wc', 'blocksCheckout' ],
	'@woocommerce/blocks-components': [ 'wc', 'blocksComponents' ],
	'@woocommerce/types': [ 'wc', 'wcTypes' ],
	'@woocommerce/sanitize': [ 'wc', 'sanitize' ],
	'@woocommerce/entities': [ 'wc', 'wcEntities' ],
};
const wcHandleMap = {
	'@woocommerce/tracks': false, // Bundle; no PHP handle needed
	'@woocommerce/blocks-registry': 'wc-blocks-registry',
	'@woocommerce/settings': 'wc-settings',
	'@woocommerce/block-data': 'wc-blocks-data-store',
	'@woocommerce/data': 'wc-store-data',
	'@woocommerce/shared-context': 'wc-blocks-shared-context',
	'@woocommerce/shared-hocs': 'wc-blocks-shared-hocs',
	'@woocommerce/price-format': 'wc-price-format',
	'@woocommerce/blocks-checkout': 'wc-blocks-checkout',
	'@woocommerce/blocks-checkout-events': 'wc-blocks-checkout-events',
	'@woocommerce/blocks-components': 'wc-blocks-components',
	'@woocommerce/types': 'wc-types',
	'@woocommerce/sanitize': 'wc-sanitize',
	'@woocommerce/entities': 'wc-entities',
};

const isWooPackageRequest = ( request ) =>
	request.startsWith( '@woocommerce/' );

const shouldBundleWooPackageInEditor = ( request ) =>
	isWooPackageRequest( request ) && request !== '@woocommerce/entities';

const getBlockJsonWithSharedEditorStyle = ( content ) => {
	const metadata = JSON.parse( content.toString() );

	if ( metadata.editorStyle ) {
		metadata.editorStyle = SHARED_EDITOR_STYLE_HANDLE;
	}

	return `${ JSON.stringify( metadata, null, '\t' ) }\n`;
};

const getEditorPackageAliases = () => ( {
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

const getAlias = ( options = {} ) => {
	let { pathPart } = options;
	pathPart = pathPart ? `${ pathPart }/` : '';
	return {
		'@woocommerce/atomic-blocks': path.resolve(
			__dirname,
			`../assets/js/${ pathPart }atomic/blocks`
		),
		'@woocommerce/atomic-utils': path.resolve(
			__dirname,
			`../assets/js/${ pathPart }atomic/utils`
		),
		'@woocommerce/base-components': path.resolve(
			__dirname,
			`../assets/js/${ pathPart }base/components/`
		),
		'@woocommerce/base-context': path.resolve(
			__dirname,
			`../assets/js/${ pathPart }base/context/`
		),
		'@woocommerce/base-hocs': path.resolve(
			__dirname,
			`../assets/js/${ pathPart }base/hocs/`
		),
		'@woocommerce/base-hooks': path.resolve(
			__dirname,
			`../assets/js/${ pathPart }base/hooks/`
		),
		'@woocommerce/base-utils': path.resolve(
			__dirname,
			`../assets/js/${ pathPart }base/utils/`
		),
		'@woocommerce/block-data': path.resolve(
			__dirname,
			`../assets/js/data`
		),
		'@woocommerce/blocks': path.resolve(
			__dirname,
			`../assets/js/${ pathPart }/blocks`
		),
		'@woocommerce/editor-components': path.resolve(
			__dirname,
			`../assets/js/${ pathPart }editor-components/`
		),
		'@woocommerce/block-hocs': path.resolve(
			__dirname,
			`../assets/js/${ pathPart }hocs`
		),
		'@woocommerce/block-settings': path.resolve(
			__dirname,
			'../assets/js/settings/blocks'
		),
		'@woocommerce/icons': path.resolve( __dirname, `../assets/js/icons` ),
		'@woocommerce/resource-previews': path.resolve(
			__dirname,
			`../assets/js/${ pathPart }previews/`
		),
		'@woocommerce/types': path.resolve( __dirname, `../assets/js/types/` ),
		'@woocommerce/utils': path.resolve( __dirname, `../assets/js/utils/` ),
		'@woocommerce/entities': path.resolve(
			__dirname,
			`../assets/js/entities/`
		),
		'react/jsx-dev-runtime': require.resolve( 'react/jsx-dev-runtime' ),
		'react/jsx-runtime': require.resolve( 'react/jsx-runtime' ),
	};
};

// Activates the `"wc-source"` conditional export declared in each
// `packages/js/*` package.json. Webpack walks the package's exports map and
// picks `./src/index.ts` directly — eliminating the need to pre-build the few
// `@woocommerce/*` packages that blocks bundles (i.e. not externalized via
// `wcDepMap`). The condition is namespaced (`wc-` prefix) so it never collides
// with third-party packages that publish their own `"source"` conditional
// export. `'...'` extends the default webpack condition list.
const getResolve = ( { alias, resolvePlugins = [] } = {} ) => ( {
	conditionNames: [ 'wc-source', '...' ],
	plugins: resolvePlugins,
	...( alias ? { alias } : {} ),
} );

const requestToExternal = ( request ) => {
	if ( request in wcDepMap ) {
		return wcDepMap[ request ];
	}
	if ( request === 'react-dom/client' ) {
		// React 18 split createRoot/hydrateRoot into react-dom/client.
		// WordPress's wp-react-dom UMD aggregates both entrypoints onto the
		// same window.ReactDOM global. DEWP's default mapper doesn't know
		// about the subpath yet
		// (https://github.com/WordPress/gutenberg/pull/77326),
		// so map it here.
		return 'ReactDOM';
	}
};

const requestToHandle = ( request ) => {
	if ( request in wcHandleMap ) {
		return wcHandleMap[ request ];
	}
	if ( request === 'react-dom/client' ) {
		return 'react-dom';
	}
};

const requestToEditorExternal = ( request ) => {
	if ( shouldBundleWooPackageInEditor( request ) ) {
		return false;
	}

	return requestToExternal( request );
};

const requestToEditorHandle = ( request ) => {
	if ( shouldBundleWooPackageInEditor( request ) ) {
		return false;
	}

	return requestToHandle( request );
};

const getProgressBarPluginConfig = ( name ) => {
	return {
		format:
			chalk.blue( `Building ${ name }` ) +
			' [:bar] ' +
			chalk.green( ':percent' ) +
			' :msg (:elapsed seconds)',
		summary: false,
		customSummary: ( time ) => {
			console.log(
				chalk.green.bold(
					`${ name } assets build completed (${ time })`
				)
			);
		},
	};
};

const getCacheGroups = () => ( {
	'base-components': {
		test: /\/assets\/js\/base\/components\//,
		name( module, chunks, cacheGroupKey ) {
			const moduleFileName = module
				.identifier()
				.split( '/' )
				.reduceRight( ( item ) => item );
			const allChunksNames = chunks
				.map( ( item ) => item.name )
				.join( '~' );
			return `${ cacheGroupKey }-${ allChunksNames }-${ moduleFileName }`;
		},
	},
	'base-context': {
		test: /\/assets\/js\/base\/context\//,
		name( module, chunks, cacheGroupKey ) {
			const moduleFileName = module
				.identifier()
				.split( '/' )
				.reduceRight( ( item ) => item );
			const allChunksNames = chunks
				.map( ( item ) => item.name )
				.join( '~' );
			return `${ cacheGroupKey }-${ allChunksNames }-${ moduleFileName }`;
		},
	},
	'base-hooks': {
		test: /\/assets\/js\/base\/hooks\//,
		name( module, chunks, cacheGroupKey ) {
			const moduleFileName = module
				.identifier()
				.split( '/' )
				.reduceRight( ( item ) => item );
			const allChunksNames = chunks
				.map( ( item ) => item.name )
				.join( '~' );
			return `${ cacheGroupKey }-${ allChunksNames }-${ moduleFileName }`;
		},
	},
	'base-utils': {
		test: /\/assets\/js\/base\/utils\//,
		name( module, chunks, cacheGroupKey ) {
			const moduleFileName = module
				.identifier()
				.split( '/' )
				.reduceRight( ( item ) => item );
			const allChunksNames = chunks
				.map( ( item ) => item.name )
				.join( '~' );
			return `${ cacheGroupKey }-${ allChunksNames }-${ moduleFileName }`;
		},
	},
} );

module.exports = {
	NODE_ENV,
	CHECK_CIRCULAR_DEPS,
	ASSET_CHECK,
	SHARED_EDITOR_STYLE_HANDLE,
	getBlockJsonWithSharedEditorStyle,
	getAlias,
	getEditorPackageAliases,
	getResolve,
	requestToHandle,
	requestToExternal,
	requestToEditorHandle,
	requestToEditorExternal,
	getProgressBarPluginConfig,
	getCacheGroups,
};
