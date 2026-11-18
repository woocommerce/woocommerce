const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const os = require( 'node:os' );
const path = require( 'node:path' );
const { test } = require( 'node:test' );
const writeE2ESiteConfig = require( '@woocommerce/extension-tooling/e2e-site-config' );

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
	const { root, pluginPath, input, defaults, output } = site( context );
	defaults.env = {
		development: { core: 'WordPress/WordPress#7.0', phpVersion: '8.4' },
		tests: { phpVersion: '8.3' },
	};
	fs.writeFileSync( input, JSON.stringify( defaults ) );
	writeE2ESiteConfig( {
		root,
		pluginPath,
		env: {
			WP_VERSION: '7.0.1',
			WC_VERSION: '11.3.0-rc.1',
			E2E_PHP_VERSION: '8.2',
		},
	} );
	const config = JSON.parse( fs.readFileSync( output, 'utf8' ) );
	assert.equal( config.core, 'https://wordpress.org/wordpress-7.0.1.zip' );
	assert.equal( config.phpVersion, '8.2' );
	assert.deepEqual( config.env, {
		development: { core: config.core, phpVersion: config.phpVersion },
		tests: { phpVersion: '8.3' },
	} );
	assert.equal(
		config.plugins[ 1 ],
		'https://github.com/woocommerce/woocommerce/releases/download/11.3.0-rc.1/woocommerce.zip'
	);
} );

test( 'keeps the configured PHP version when the host exports PHP_VERSION', ( context ) => {
	const { root, pluginPath, output } = site( context );
	writeE2ESiteConfig( { root, pluginPath, env: { PHP_VERSION: '8.4.26' } } );
	const config = JSON.parse( fs.readFileSync( output, 'utf8' ) );
	assert.equal( config.phpVersion, '8.3' );
} );

test( 'rejects invalid explicit PHP and WooCommerce versions', ( context ) => {
	const { root, pluginPath, output } = site( context );
	fs.writeFileSync( output, '{}' );
	for ( const [ env, key ] of [
		[ { E2E_PHP_VERSION: '' }, 'E2E_PHP_VERSION' ],
		[ { E2E_PHP_VERSION: '8.2.1' }, 'E2E_PHP_VERSION' ],
		[ { E2E_PHP_VERSION: '8.2-rc.1' }, 'E2E_PHP_VERSION' ],
		[ { WC_VERSION: '11.3' }, 'WC_VERSION' ],
		[ { WC_VERSION: 'v11.3.0' }, 'WC_VERSION' ],
	] ) {
		assert.throws(
			() => writeE2ESiteConfig( { root, pluginPath, env } ),
			( error ) => error.message.includes( key )
		);
		assert.equal( fs.readFileSync( output, 'utf8' ), '{}' );
	}
} );

test( 'keeps one debug log mount after configuration and development overrides', ( context ) => {
	const { root, pluginPath, output } = site( context );
	writeE2ESiteConfig( {
		root,
		pluginPath,
		env: {},
		configure( config ) {
			config.mappings = {
				'wp-content/debug.log': './old.log',
				'wp-content/mu-plugins/example.php': './example.php',
			};
			config.env = {
				development: {
					mappings: {
						'./wp-content/debug.log': './development.log',
						'wp-content/wp-env.php': './development.php',
					},
				},
				tests: {
					mappings: { './wp-content/debug.log': './tests.log' },
				},
			};
		},
	} );
	const config = JSON.parse( fs.readFileSync( output, 'utf8' ) );
	assert.deepEqual( config.mappings, {
		'wp-content/mu-plugins/example.php': './example.php',
		'./wp-content/debug.log': './tests/_output/e2e-debug.log',
	} );
	assert.deepEqual( config.env.development.mappings, {
		'wp-content/wp-env.php': './development.php',
	} );
	assert.deepEqual( config.env.tests.mappings, {
		'./wp-content/debug.log': './tests.log',
	} );
} );

test( 'rejects invalid plugins and mappings before writing a config', ( context ) => {
	const { root, pluginPath, input, defaults, output } = site( context );
	for ( const [ overrides, error ] of [
		[ { plugins: '.' }, 'plugins must be an array of non-empty strings' ],
		[
			{ plugins: [ '.', 1 ] },
			'plugins must be an array of non-empty strings',
		],
		[ { mappings: [] }, 'mappings must be an object' ],
		[
			{ mappings: { 'wp-content/mu-plugins/custom.php': 42 } },
			'mappings must be an object of non-empty strings',
		],
	] ) {
		fs.writeFileSync(
			input,
			JSON.stringify( { ...defaults, ...overrides } )
		);
		assert.throws(
			() => writeE2ESiteConfig( { root, pluginPath, env: {} } ),
			( failure ) => failure.message.includes( error )
		);
		assert.equal( fs.existsSync( output ), false );
	}
} );

test( 'preserves the previous output when a plugin override is invalid', ( context ) => {
	const { root, pluginPath, output } = site( context );
	const fixtures = [
		{
			configure( config ) {
				config.plugins.push( undefined );
			},
		},
		{
			configure( config ) {
				delete config.plugins[ 2 ];
			},
		},
		{
			configure( config ) {
				config.plugins = null;
			},
			env: { WC_VERSION: 'nightly' },
		},
		...[ '', ' ' ].map( ( source ) => ( {
			configure( config ) {
				config.plugins.push( source );
			},
		} ) ),
		{ woocommerce: { latest: 42 } },
		{ woocommerce: { latest: '' } },
		{
			env: { WC_VERSION: 'nightly' },
			woocommerce: { nightly: '' },
		},
	];

	fs.writeFileSync( output, '{}' );
	for ( const options of fixtures ) {
		assert.throws(
			() =>
				writeE2ESiteConfig( { root, pluginPath, env: {}, ...options } ),
			( failure ) =>
				failure.message.includes(
					'.wp-env.e2e.json plugins must be an array of non-empty strings'
				)
		);
		assert.equal( fs.readFileSync( output, 'utf8' ), '{}' );
	}
} );

test( 'preserves the previous output when a mapping override is invalid', ( context ) => {
	const { root, pluginPath, output } = site( context );
	fs.writeFileSync( output, '{}' );
	for ( const mappings of [
		[],
		{ 'wp-content/mu-plugins/custom.php': 42 },
		{ 'wp-content/mu-plugins/custom.php': ' ' },
	] ) {
		assert.throws(
			() =>
				writeE2ESiteConfig( {
					root,
					pluginPath,
					env: {},
					configure( config ) {
						config.mappings = mappings;
					},
				} ),
			( failure ) =>
				failure.message.includes( '.wp-env.e2e.json mappings' )
		);
		assert.equal( fs.readFileSync( output, 'utf8' ), '{}' );
	}
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

test( 'replaces the standard WooCommerce ZIP in a pinned job', ( context ) => {
	const { root, pluginPath, input, defaults, output } = site( context );
	defaults.plugins[ 1 ] =
		'https://downloads.wordpress.org/plugin/woocommerce.zip';
	fs.writeFileSync( input, JSON.stringify( defaults ) );
	writeE2ESiteConfig( {
		root,
		pluginPath,
		env: { WC_VERSION: '11.3.0' },
	} );
	const config = JSON.parse( fs.readFileSync( output, 'utf8' ) );
	assert.equal(
		config.plugins[ 1 ],
		'https://github.com/woocommerce/woocommerce/releases/download/11.3.0/woocommerce.zip'
	);
} );

test( 'rejects a WooCommerce override without a WooCommerce ZIP mount', ( context ) => {
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
		/must include a WooCommerce ZIP mount/
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
