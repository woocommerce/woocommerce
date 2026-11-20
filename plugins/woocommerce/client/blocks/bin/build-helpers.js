/**
 * External dependencies
 */
const path = require( 'path' );

// See also @woocommerce/dependency-extraction-webpack-plugin/assets/packages and
// docs/internal-developers/enqueueable-packages/README.md. They should stay in sync with this map.
// The dependency extraction plugin will backfill any missing mapping here. Duplicates exist because
// this file has switched between Woo and WordPress versions of the plugin.
// As of 2026, it uses the Woo version to address pnpm peer dependency issues and support
// filesystem cache.
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

const getAlias = ( options = {} ) => {
	let { pathPart } = options;
	pathPart = pathPart ? `${ pathPart }/` : '';
	return {
		'@woocommerce/product-elements': path.resolve(
			__dirname,
			`../assets/js/${ pathPart }blocks/product-elements-blocks`
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
			`../packages/public-api/block-data`
		),
		'@woocommerce/blocks-checkout': path.resolve(
			__dirname,
			`../packages/public-api/blocks-checkout`
		),
		'@woocommerce/blocks-checkout-events': path.resolve(
			__dirname,
			`../packages/public-api/blocks-checkout-events`
		),
		'@woocommerce/blocks-components': path.resolve(
			__dirname,
			`../packages/public-api/blocks-components`
		),
		'@woocommerce/blocks-registry': path.resolve(
			__dirname,
			`../packages/public-api/blocks-registry`
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
		'@woocommerce/price-format': path.resolve(
			__dirname,
			`../packages/public-api/price-format`
		),
		'@woocommerce/settings': path.resolve(
			__dirname,
			`../packages/public-api/settings`
		),
		'@woocommerce/shared-context': path.resolve(
			__dirname,
			`../packages/public-api/shared-context`
		),
		'@woocommerce/shared-hocs': path.resolve(
			__dirname,
			`../packages/public-api/shared-hocs`
		),
		'@woocommerce/types': path.resolve(
			__dirname,
			`../packages/public-api/types/`
		),
		'@woocommerce/utils': path.resolve( __dirname, `../assets/js/utils/` ),
		'@woocommerce/entities': path.resolve(
			__dirname,
			`../packages/internal/entities/`
		),
		'react/jsx-dev-runtime': require.resolve( 'react/jsx-dev-runtime' ),
		'react/jsx-runtime': require.resolve( 'react/jsx-runtime' ),
	};
};

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

module.exports = {
	getAlias,
	requestToHandle,
	requestToExternal,
};
