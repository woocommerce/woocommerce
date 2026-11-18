const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const os = require( 'node:os' );
const path = require( 'node:path' );
const { test } = require( 'node:test' );
const writeE2ESiteConfig = require( '../src/e2e-site-config' );

const latestWooCommerce =
	'https://downloads.wordpress.org/plugin/woocommerce.latest-stable.zip';

function site( context ) {
	const root = fs.mkdtempSync( path.join( os.tmpdir(), 'woo-e2e-config-' ) );
	context.after( () => fs.rmSync( root, { recursive: true, force: true } ) );
	const defaults = {
		plugins: [ '.', latestWooCommerce, './fixture' ],
		core: null,
		phpVersion: '8.3',
		mappings: { 'wp-content/mu-plugins/example.php': './example.php' },
	};
	const input = path.join( root, '.wp-env.test.json' );
	fs.writeFileSync( input, JSON.stringify( defaults ) );
	return {
		root,
		defaults,
		input,
		output: path.join( root, '.wp-env.e2e.json' ),
		pluginPath: './deploy/e2e-plugin/example',
	};
}

test( 'mounts the packaged plugin and adds a separate debug log', ( context ) => {
	const { root, pluginPath, defaults, input, output } = site( context );
	writeE2ESiteConfig( { root, pluginPath, env: {} } );
	assert.deepEqual( JSON.parse( fs.readFileSync( output, 'utf8' ) ), {
		...defaults,
		plugins: [ pluginPath, latestWooCommerce, './fixture' ],
		mappings: {
			...defaults.mappings,
			'./wp-content/debug.log': './tests/_output/e2e-debug.log',
		},
	} );
	assert.deepEqual(
		JSON.parse( fs.readFileSync( input, 'utf8' ) ),
		defaults
	);
} );

test( 'sets pinned WordPress, WooCommerce and PHP versions', ( context ) => {
	const { root, pluginPath, output } = site( context );
	writeE2ESiteConfig( {
		root,
		pluginPath,
		env: {
			WP_VERSION: '7.0.1',
			WC_VERSION: '11.3.0-rc.1',
			PHP_VERSION: '8.2',
		},
	} );
	const config = JSON.parse( fs.readFileSync( output, 'utf8' ) );
	assert.equal( config.core, 'https://wordpress.org/wordpress-7.0.1.zip' );
	assert.equal( config.phpVersion, '8.2' );
	assert.equal(
		config.plugins[ 1 ],
		'https://github.com/woocommerce/woocommerce/releases/download/11.3.0-rc.1/woocommerce.zip'
	);
} );

test( 'preserves local integration mappings and the WooCommerce nightly mount', ( context ) => {
	const { root, pluginPath, output } = site( context );
	const nightly = './deploy/e2e-integrations/woocommerce';
	writeE2ESiteConfig( {
		root,
		pluginPath,
		env: {
			WP_VERSION: 'nightly',
			WC_VERSION: 'nightly',
			PHP_VERSION: 'nightly',
		},
		woocommerce: { nightly },
		configure( config ) {
			config.plugins.push( './deploy/e2e-integrations/order-barcodes' );
			config.mappings[ 'wp-content/mu-plugins/barcodes.php' ] =
				'./empty.php';
		},
	} );
	const config = JSON.parse( fs.readFileSync( output, 'utf8' ) );
	assert.equal(
		config.core,
		'https://wordpress.org/nightly-builds/wordpress-latest.zip'
	);
	assert.equal( config.phpVersion, '8.3' );
	assert.deepEqual( config.plugins, [
		pluginPath,
		nightly,
		'./fixture',
		'./deploy/e2e-integrations/order-barcodes',
	] );
	assert.equal(
		config.mappings[ 'wp-content/mu-plugins/barcodes.php' ],
		'./empty.php'
	);
} );

test( 'can override the latest WooCommerce source and add mappings', ( context ) => {
	const { root, pluginPath, input, defaults, output } = site( context );
	delete defaults.mappings;
	fs.writeFileSync( input, JSON.stringify( defaults ) );
	const latest = 'https://downloads.wordpress.org/plugin/woocommerce.zip';
	writeE2ESiteConfig( {
		root,
		pluginPath,
		env: { WC_VERSION: 'latest' },
		woocommerce: { latest },
	} );
	const config = JSON.parse( fs.readFileSync( output, 'utf8' ) );
	assert.equal( config.plugins[ 1 ], latest );
	assert.deepEqual( config.mappings, {
		'./wp-content/debug.log': './tests/_output/e2e-debug.log',
	} );
} );

test( 'rejects a WooCommerce override without the latest-stable mount', ( context ) => {
	const { root, pluginPath, input, defaults, output } = site( context );
	fs.writeFileSync(
		input,
		JSON.stringify( { ...defaults, plugins: [ '.' ] } )
	);
	assert.throws(
		() =>
			writeE2ESiteConfig( {
				root,
				pluginPath,
				env: { WC_VERSION: 'nightly' },
			} ),
		/must install WooCommerce latest-stable/
	);
	assert.equal( fs.existsSync( output ), false );
} );

test( 'rejects a missing checkout mount', ( context ) => {
	const { root, pluginPath, input, defaults, output } = site( context );
	fs.writeFileSync( input, JSON.stringify( { ...defaults, plugins: [] } ) );
	assert.throws(
		() => writeE2ESiteConfig( { root, pluginPath } ),
		/mount the checkout/
	);
	assert.equal( fs.existsSync( output ), false );
} );
