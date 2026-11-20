import path from 'node:path';
import { createRequire } from 'node:module';

export const REPO = path.resolve( import.meta.dirname, '../../..' );
export const BLOCKS = path.join( REPO, 'plugins/woocommerce/client/blocks' );
export const ADMIN = path.join( REPO, 'plugins/woocommerce/client/admin' );
export const PACKAGES = path.join( REPO, 'packages/js' );
export const ASSETS = path.join( REPO, 'plugins/woocommerce/assets/client' );

const DEWP = path.join( PACKAGES, 'dependency-extraction-webpack-plugin' );
const dewpRequire = createRequire( path.join( DEWP, 'package.json' ) );
const wooPackages = dewpRequire( './assets/packages.js' );
const wpUtil = dewpRequire(
	path.join(
		path.dirname(
			dewpRequire.resolve(
				'@wordpress/dependency-extraction-webpack-plugin/package.json'
			)
		),
		'lib/util.js'
	)
);

// Same rules as packages/js/dependency-extraction-webpack-plugin/src/index.js.
const wooRequestToExternal = ( request ) => {
	if ( ! wooPackages.includes( request ) ) {
		return undefined;
	}
	const handle = request.substring( '@woocommerce/'.length );
	const irregular = {
		'block-data': [ 'wc', 'wcBlocksData' ],
		'blocks-registry': [ 'wc', 'wcBlocksRegistry' ],
		settings: [ 'wc', 'wcSettings' ],
	};
	return irregular[ handle ] || [ 'wc', wpUtil.camelCaseDash( handle ) ];
};
const wooRequestToHandle = ( request ) => {
	if ( ! wooPackages.includes( request ) ) {
		return undefined;
	}
	const handle = request.substring( '@woocommerce/'.length );
	const irregular = {
		data: 'wc-store-data',
		'block-data': 'wc-blocks-data-store',
		'csv-export': 'wc-csv',
	};
	return irregular[ handle ] || 'wc-' + handle;
};

/**
 * The global a request maps to, in the plugin's order: the config's own
 * rule, then WooCommerce packages, then WordPress packages.
 */
export const externalsFor = ( userToExternal, userToHandle ) => ( {
	toGlobal( request ) {
		let external = userToExternal?.( request );
		if ( external === undefined ) {
			external = wooRequestToExternal( request );
		}
		if ( external === undefined ) {
			external = wpUtil.defaultRequestToExternal( request );
		}
		return external || null;
	},
	toHandle( request ) {
		return (
			userToHandle?.( request ) ||
			wooRequestToHandle( request ) ||
			wpUtil.defaultRequestToHandle( request ) ||
			request
		);
	},
} );

export const wpModuleExternal = wpUtil.defaultRequestToExternalModule;

// From `extends @wordpress/browserslist-config` on this checkout.
export const TARGET = [ 'chrome151', 'edge152', 'firefox158', 'safari18.5', 'ios18.5' ];

export const paramCase = ( name ) =>
	name.replace( /([a-z0-9])([A-Z])/g, '$1-$2' ).toLowerCase();
