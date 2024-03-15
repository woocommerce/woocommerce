/* eslint-disable no-console */
/**
 * External dependencies
 */
const path = require( 'path' );
const chalk = require( 'chalk' );
const NODE_ENV = process.env.NODE_ENV || 'development';
const CHECK_CIRCULAR_DEPS = process.env.CHECK_CIRCULAR_DEPS || false;
const ASSET_CHECK = process.env.ASSET_CHECK === 'true';

const wcDepMap = {
	'@woocommerce/blocks-registry': [ 'wc', 'wcBlocksRegistry' ],
	'@woocommerce/settings': [ 'wc', 'wcSettings' ],
	'@woocommerce/block-data': [ 'wc', 'wcBlocksData' ],
	'@woocommerce/data': [ 'wc', 'data' ],
	'@woocommerce/shared-context': [ 'wc', 'wcBlocksSharedContext' ],
	'@woocommerce/shared-hocs': [ 'wc', 'wcBlocksSharedHocs' ],
	'@woocommerce/price-format': [ 'wc', 'priceFormat' ],
	'@woocommerce/blocks-checkout': [ 'wc', 'blocksCheckout' ],
	'@woocommerce/blocks-components': [ 'wc', 'blocksComponents' ],
	'@woocommerce/interactivity': [ 'wc', '__experimentalInteractivity' ],
};

const wcHandleMap = {
	'@woocommerce/blocks-registry': 'wc-blocks-registry',
	'@woocommerce/settings': 'wc-settings',
	'@woocommerce/block-data': 'wc-blocks-data-store',
	'@woocommerce/data': 'wc-store-data',
	'@woocommerce/shared-context': 'wc-blocks-shared-context',
	'@woocommerce/shared-hocs': 'wc-blocks-shared-hocs',
	'@woocommerce/price-format': 'wc-price-format',
	'@woocommerce/blocks-checkout': 'wc-blocks-checkout',
	'@woocommerce/blocks-components': 'wc-blocks-components',
	'@woocommerce/interactivity': 'wc-interactivity',
};

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
		'@woocommerce/interactivity': path.resolve(
			__dirname,
			`../assets/js/${ pathPart }interactivity/`
		),
		'@woocommerce/base-utils': path.resolve(
			__dirname,
			`../assets/js/${ pathPart }base/utils/`
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
		'@woocommerce/templates': path.resolve(
			__dirname,
			`../assets/js/templates/`
		),
		'react/jsx-dev-runtime': require.resolve( 'react/jsx-dev-runtime.js' ),
		'react/jsx-runtime': require.resolve( 'react/jsx-runtime.js' ),
	};
};

const requestToExternal = ( request ) => {
	if ( wcDepMap[ request ] ) {
		return wcDepMap[ request ];
	}
};

const requestToHandle = ( request ) => {
	if ( wcHandleMap[ request ] ) {
		return wcHandleMap[ request ];
	}
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
	getAlias,
	requestToHandle,
	requestToExternal,
	getProgressBarPluginConfig,
	getCacheGroups,
};
