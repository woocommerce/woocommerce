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
const isMountSource = ( source ) =>
	typeof source === 'string' && source.trim() !== '';

function assertPluginSources( plugins, filename ) {
	if (
		! Array.isArray( plugins ) ||
		plugins.findIndex( ( source ) => ! isMountSource( source ) ) !== -1
	) {
		throw new Error(
			`${ filename } plugins must be an array of non-empty strings.`
		);
	}
}

function assertMappings( mappings, filename ) {
	if (
		! mappings ||
		typeof mappings !== 'object' ||
		Array.isArray( mappings ) ||
		! Object.values( mappings ).every( isMountSource )
	) {
		throw new Error(
			`${ filename } mappings must be an object of non-empty strings.`
		);
	}
}

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
 * @param {Object}   [options.env=process.env] WordPress, WooCommerce and explicit E2E PHP version variables.
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
	assertPluginSources( config.plugins, '.wp-env.test.json' );
	const checkout = config.plugins.indexOf( '.' );
	if ( checkout === -1 ) {
		throw new Error( '.wp-env.test.json must mount the checkout as ".".' );
	}
	config.plugins[ checkout ] = pluginPath;
	config.mappings ??= {};
	assertMappings( config.mappings, '.wp-env.test.json' );
	configure?.( config );

	const { WC_VERSION = '', WP_VERSION, E2E_PHP_VERSION } = env;
	let wooSource = woocommerce.latest ?? null;
	if ( WC_VERSION === 'nightly' || WC_VERSION === 'trunk' ) {
		wooSource = woocommerce.nightly ?? nightlyWooCommerce;
	} else if ( WC_VERSION !== '' && WC_VERSION !== 'latest' ) {
		if (
			typeof WC_VERSION !== 'string' ||
			! /^\d+\.\d+\.\d+(?:-[a-zA-Z0-9]+(?:[.-][a-zA-Z0-9]+)*)?$/.test(
				WC_VERSION
			)
		) {
			throw new Error(
				`Invalid WC_VERSION: ${ WC_VERSION } (expected latest, nightly or a release tag).`
			);
		}
		wooSource =
			'https://github.com/woocommerce/woocommerce/releases/download/' +
			WC_VERSION +
			'/woocommerce.zip';
	}
	if ( wooSource !== null && Array.isArray( config.plugins ) ) {
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
	if ( E2E_PHP_VERSION !== undefined ) {
		if (
			typeof E2E_PHP_VERSION !== 'string' ||
			! /^\d+\.\d+$/.test( E2E_PHP_VERSION )
		) {
			throw new Error(
				'Invalid E2E_PHP_VERSION (expected major.minor).'
			);
		}
		setSiteVersion( config, 'phpVersion', E2E_PHP_VERSION );
	}

	assertPluginSources( config.plugins, '.wp-env.e2e.json' );
	assertMappings( config.mappings, '.wp-env.e2e.json' );

	const debugLog = 'wp-content/debug.log';
	const debugLogMount = `./${ debugLog }`;
	const developmentMappings = config.env?.development?.mappings;
	delete config.mappings[ debugLog ];
	delete developmentMappings?.[ debugLog ];
	delete developmentMappings?.[ debugLogMount ];
	config.mappings[ debugLogMount ] = './tests/_output/e2e-debug.log';
	fs.writeFileSync(
		path.join( root, '.wp-env.e2e.json' ),
		JSON.stringify( config )
	);
}

module.exports = writeE2ESiteConfig;
