/**
 * External dependencies
 */
const path = require( 'path' );

const NODE_ENV = process.env.NODE_ENV || 'development';
const FORCE_MAP = process.env.FORCE_MAP || false;
const CHECK_CIRCULAR_DEPS = process.env.CHECK_CIRCULAR_DEPS || false;

// If a package is not available, or missing functionality, in an old but __supported__ version of WordPress, it should be listed here.
// Some packages are not available in legacy versions of WordPress, so we don't want to extract them.
const requiredPackagesInWPLegacy = [];

const wcDepMap = {
	'@woocommerce/blocks-registry': [ 'wc', 'wcBlocksRegistry' ],
	'@woocommerce/settings': [ 'wc', 'wcSettings' ],
	'@woocommerce/block-data': [ 'wc', 'wcBlocksData' ],
	'@woocommerce/shared-context': [ 'wc', 'wcSharedContext' ],
	'@woocommerce/shared-hocs': [ 'wc', 'wcSharedHocs' ],
	'@woocommerce/price-format': [ 'wc', 'priceFormat' ],
	'@woocommerce/blocks-checkout': [ 'wc', 'blocksCheckout' ],
};

const wcHandleMap = {
	'@woocommerce/blocks-registry': 'wc-blocks-registry',
	'@woocommerce/settings': 'wc-settings',
	'@woocommerce/block-settings': 'wc-settings',
	'@woocommerce/block-data': 'wc-blocks-data-store',
	'@woocommerce/shared-context': 'wc-shared-context',
	'@woocommerce/shared-hocs': 'wc-shared-hocs',
	'@woocommerce/price-format': 'wc-price-format',
	'@woocommerce/blocks-checkout': 'wc-blocks-checkout',
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
		'@woocommerce/base-utils': path.resolve(
			__dirname,
			`../assets/js/${ pathPart }base/utils/`
		),
		'@woocommerce/editor-components': path.resolve(
			__dirname,
			`../assets/js/${ pathPart }editor-components/`
		),
		'@woocommerce/block-hocs': path.resolve(
			__dirname,
			`../assets/js/${ pathPart }hocs`
		),
		'@woocommerce/blocks-registry': path.resolve(
			__dirname,
			'../assets/js/blocks-registry'
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
	};
};

function findModuleMatch( module, match ) {
	if ( module.request && match.test( module.request ) ) {
		return true;
	} else if ( module.issuer ) {
		return findModuleMatch( module.issuer, match );
	}
	return false;
}

const requestToExternal = ( request ) => {
	if ( requiredPackagesInWPLegacy.includes( request ) ) {
		return false;
	}
	if ( wcDepMap[ request ] ) {
		return wcDepMap[ request ];
	}
};

const requestToExternalInsideGB = ( request ) => {
	if ( request === 'wordpress-components' ) {
		return [ 'wp', 'components' ];
	}
	return requestToExternal( request );
};

const requestToHandle = ( request ) => {
	if ( requiredPackagesInWPLegacy.includes( request ) ) {
		return false;
	}
	if ( wcHandleMap[ request ] ) {
		return wcHandleMap[ request ];
	}
};

const requestToHandleInsideGB = ( request ) => {
	if ( request === 'wordpress-components' ) {
		return 'wp-components';
	}
	return requestToHandle( request );
};

module.exports = {
	NODE_ENV,
	FORCE_MAP,
	CHECK_CIRCULAR_DEPS,
	getAlias,
	findModuleMatch,
	requestToHandle,
	requestToExternal,
	requestToHandleInsideGB,
	requestToExternalInsideGB,
};
