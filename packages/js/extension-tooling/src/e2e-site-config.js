const fs = require( 'node:fs' );
const path = require( 'node:path' );

const latestWooCommerce =
	'https://downloads.wordpress.org/plugin/woocommerce.latest-stable.zip';
const stableWooCommerce =
	'https://downloads.wordpress.org/plugin/woocommerce.zip';
const nightlyWooCommerce =
	'https://github.com/woocommerce/woocommerce/releases/download/nightly/woocommerce-trunk-nightly.zip';
// Match dotted version numbers and pre-release tags such as 11.3.0-rc.1.
const isVersionNumber = ( value ) =>
	typeof value === 'string' && /^\d+(\.\d+)*(-\w+(\.\w+)*)*$/.test( value );

function setSiteVersion( config, option, value ) {
	config[ option ] = value;
	const development = config.env?.development;
	if ( development && Object.hasOwn( development, option ) ) {
		development[ option ] = value;
	}
}

/**
 * Generate the browser test site config from the repo's wp-env tests site.
 *
 * @param {Object}   options                   Site setup options.
 * @param {string}   options.root              Extension checkout directory.
 * @param {string}   options.pluginPath        Relative path to the unpacked production plugin.
 * @param {Object}   [options.env=process.env] WordPress, WooCommerce and PHP version variables.
 * @param {Object}   [options.woocommerce]     Optional latest and nightly WooCommerce install sources.
 * @param {Function} [options.configure]       Set repo-specific plugins and mappings.
 */
function writeE2ESiteConfig( {
	root,
	pluginPath,
	env = process.env,
	woocommerce = {},
	configure,
} ) {
	const config = JSON.parse(
		fs.readFileSync( path.join( root, '.wp-env.test.json' ), 'utf8' )
	);
	if (
		! Array.isArray( config.plugins ) ||
		! config.plugins.every( ( source ) => typeof source === 'string' )
	) {
		throw new Error(
			'.wp-env.test.json plugins must be an array of strings.'
		);
	}
	const checkout = config.plugins.indexOf( '.' );
	if ( checkout === -1 ) {
		throw new Error( '.wp-env.test.json must mount the checkout as ".".' );
	}
	config.plugins[ checkout ] = pluginPath;
	config.mappings ??= {};
	if (
		typeof config.mappings !== 'object' ||
		Array.isArray( config.mappings )
	) {
		throw new Error( '.wp-env.test.json mappings must be an object.' );
	}
	config.mappings[ './wp-content/debug.log' ] =
		'./tests/_output/e2e-debug.log';
	configure?.( config );

	const { WC_VERSION, WP_VERSION, PHP_VERSION } = env;
	let wooSource = woocommerce.latest;
	if ( WC_VERSION === 'nightly' ) {
		wooSource = woocommerce.nightly ?? nightlyWooCommerce;
	} else if ( isVersionNumber( WC_VERSION ) ) {
		wooSource =
			'https://github.com/woocommerce/woocommerce/releases/download/' +
			WC_VERSION +
			'/woocommerce.zip';
	}
	if ( wooSource ) {
		const wooIndex = config.plugins.findIndex(
			( source ) =>
				source === latestWooCommerce || source === stableWooCommerce
		);
		if ( wooIndex === -1 ) {
			throw new Error(
				'.wp-env.test.json must include a WooCommerce ZIP mount.'
			);
		}
		config.plugins[ wooIndex ] = wooSource;
	}

	if ( WP_VERSION === 'nightly' ) {
		setSiteVersion(
			config,
			'core',
			'https://wordpress.org/nightly-builds/wordpress-latest.zip'
		);
	} else if ( isVersionNumber( WP_VERSION ) ) {
		setSiteVersion(
			config,
			'core',
			'https://wordpress.org/wordpress-' + WP_VERSION + '.zip'
		);
	}
	if ( isVersionNumber( PHP_VERSION ) ) {
		setSiteVersion( config, 'phpVersion', PHP_VERSION );
	}

	fs.writeFileSync(
		path.join( root, '.wp-env.e2e.json' ),
		JSON.stringify( config )
	);
}

module.exports = writeE2ESiteConfig;
