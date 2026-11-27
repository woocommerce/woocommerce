const assert = require( 'node:assert/strict' );
const { spawnSync } = require( 'node:child_process' );
const fs = require( 'node:fs' );
const os = require( 'node:os' );
const path = require( 'node:path' );
const { test } = require( 'node:test' );

const reader = path.join( __dirname, '..', 'bin', 'woo-config.sh' );

function fixture( context, config ) {
	const directory = fs.realpathSync(
		fs.mkdtempSync( path.join( os.tmpdir(), 'woo-config-' ) )
	);
	context.after( () =>
		fs.rmSync( directory, { recursive: true, force: true } )
	);
	fs.writeFileSync(
		path.join( directory, 'package.json' ),
		JSON.stringify( { name: 'example-extension', config: { woo: config } } )
	);
	return directory;
}

test( 'reads config from the extension through an npm-style bin symlink', ( context ) => {
	const directory = fixture( context, {
		capabilities: { js: false },
		zip: {
			build: '',
			phpCompatibility: { testVersion: '7.4-8.5' },
		},
	} );
	const bin = path.join( directory, 'node_modules', '.bin' );
	fs.mkdirSync( bin, { recursive: true } );
	const link = path.join( bin, 'woo-extension-config' );
	fs.symlinkSync( path.relative( bin, reader ), link );
	const output = path.join( directory, 'github-output' );
	const result = spawnSync( 'bash', [ link, '--github-output' ], {
		cwd: directory,
		encoding: 'utf8',
		env: { ...process.env, GITHUB_OUTPUT: output },
	} );
	assert.equal( result.status, 0, result.stderr );
	const config = JSON.parse( fs.readFileSync( output, 'utf8' ).slice( 5 ) );
	assert.equal( config.slug, 'example-extension' );
	assert.equal( config.capabilities.js, false );
	assert.equal( config.zip.build, '' );
	assert.equal( config.zip.root, 'example-extension' );
	assert.deepEqual( config.zip.phpCompatibility, {
		testVersion: '7.4-8.5',
	} );
} );

test( 'rejects invalid extension config before producing outputs', ( context ) => {
	const directory = fixture( context, { capabilities: { js: 'false' } } );
	const result = spawnSync( 'bash', [ reader ], {
		cwd: directory,
		encoding: 'utf8',
	} );
	assert.equal( result.status, 1 );
	assert.equal( result.stdout, '' );
	assert.match(
		result.stderr,
		/config\.woo\.capabilities\.js: expected boolean/
	);
} );

test( 'turns on E2E private plugins when an entry lists the e2e suite', ( context ) => {
	function e2ePrivatePlugins( suites ) {
		const result = spawnSync( 'bash', [ reader ], {
			cwd: fixture( context, {
				privatePlugins: [ { slug: 'example-addon', suites } ],
			} ),
			encoding: 'utf8',
		} );
		assert.equal( result.status, 0, result.stderr );
		return JSON.parse( result.stdout ).e2e.privatePlugins;
	}
	assert.equal( e2ePrivatePlugins( [ 'unit', 'e2e' ] ), true );
	assert.equal( e2ePrivatePlugins( [ 'unit' ] ), false );
} );

test( 'preserves an older config without the ZIP PHP compatibility opt-in', ( context ) => {
	const result = spawnSync( 'bash', [ reader ], {
		cwd: fixture( context, {} ),
		encoding: 'utf8',
	} );
	assert.equal( result.status, 0, result.stderr );
	assert.equal( JSON.parse( result.stdout ).zip.phpCompatibility, null );
} );

test( 'plans PHPUnit legs using the plugin headers and requested versions', ( context ) => {
	const directory = fixture( context, {
		phpunit: {
			phpInput: 'replace',
			legs: [
				{ php: '7.4', wp: 'minimum', wc: 'minimum' },
				{ php: '8.2', wp: 'previous', wc: 'latest' },
				{ php: '8.4', defaultOnly: true },
				{ php: '8.5', wp: '7.0', scheduledOnly: true },
			],
		},
		privatePlugins: [
			{
				slug: 'example-addon',
				minimumPattern: 'Addon\\Example minimum: %s',
			},
		],
		integration: {
			legs: [
				{
					php: '8.4',
					script: 'test:php:integration',
					plugins: { 'example-addon': 'below-minimum' },
				},
			],
		},
	} );
	fs.writeFileSync(
		path.join( directory, 'example-extension.php' ),
		'<?php\r\n/**\r\n * Requires at least: 6.8\r\n * WC requires at least: 10.4\r\n * Addon\\Example minimum: 2.3.4\r\n */\r\n'
	);
	function readMatrix( inputs = {} ) {
		const result = spawnSync( 'bash', [ reader, '--phpunit-matrix' ], {
			cwd: directory,
			encoding: 'utf8',
			env: {
				...process.env,
				PREVIOUS_WP: '6.9',
				REQUESTED_PHP: '',
				REQUESTED_WC: '',
				REQUESTED_WP: '',
				SCHEDULED: 'false',
				SUITE: 'unit',
				...inputs,
			},
		} );
		assert.equal( result.status, 0, result.stderr );
		return JSON.parse( result.stdout );
	}
	const versions = ( legs ) =>
		legs.map( ( { php, wp, wc } ) => [ php, wp, wc ] );
	assert.deepEqual( versions( readMatrix() ), [
		[ '7.4', '6.8', '10.4.0' ],
		[ '8.2', '6.9', 'latest' ],
		[ '8.4', 'latest', 'latest' ],
	] );
	assert.deepEqual(
		versions(
			readMatrix( {
				REQUESTED_PHP: '8.4',
				REQUESTED_WC: '11.2.0',
				REQUESTED_WP: '7.1',
				SCHEDULED: 'true',
			} )
		),
		[
			[ '7.4', '6.8', '10.4.0' ],
			[ '8.4', '7.1', '11.2.0' ],
			[ '8.5', '7.0', '11.2.0' ],
		]
	);
	const [ leg ] = readMatrix( { SUITE: 'integration' } );
	assert.equal( leg.script, 'test:php:integration' );
	assert.deepEqual( leg.pluginVersions, { 'example-addon': '<2.3.4' } );
} );
