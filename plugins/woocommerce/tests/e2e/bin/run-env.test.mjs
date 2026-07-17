import { test } from 'node:test';
import assert from 'node:assert/strict';
import { mkdtempSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { parseArgs, isCi, sanitizeEnv, ALLOWED_WP_ENV_VARS, readWcVersion, computeHash, readState, writeState, decide, MAX_AGE_MS } from './run-env.mjs';

test( 'parseArgs extracts --rebuild and passes the rest through', () => {
	assert.deepEqual( parseArgs( [ '--rebuild', '--debug' ] ), {
		rebuild: true,
		passthrough: [ '--debug' ],
	} );
	assert.deepEqual( parseArgs( [ '--debug' ] ), {
		rebuild: false,
		passthrough: [ '--debug' ],
	} );
	assert.deepEqual( parseArgs( [] ), { rebuild: false, passthrough: [] } );
} );

test( 'isCi is true only for a non-empty CI var', () => {
	assert.equal( isCi( { CI: 'true' } ), true );
	assert.equal( isCi( { CI: '' } ), false );
	assert.equal( isCi( {} ), false );
} );

test( 'sanitizeEnv strips non-allowlisted WP_ENV_* vars', () => {
	const out = sanitizeEnv( {
		PATH: '/usr/bin',
		WP_ENV_CORE: 'https://wordpress.org/wordpress-latest.zip',
		WP_ENV_PHP_VERSION: '8.1',
		WP_ENV_PORT: '9001',
		WP_ENV_TESTS_PORT: '9002',
		WP_ENV_HOME: '/tmp/x',
		WP_ENV_LIFECYCLE_SCRIPT_AFTER_START: 'echo hi',
	} );
	assert.equal( out.PATH, '/usr/bin' );
	assert.equal( out.WP_ENV_CORE, 'https://wordpress.org/wordpress-latest.zip' );
	assert.equal( out.WP_ENV_PHP_VERSION, '8.1' );
	assert.equal( out.WP_ENV_PORT, '9001' );
	assert.equal( 'WP_ENV_TESTS_PORT' in out, false );
	assert.equal( 'WP_ENV_HOME' in out, false );
	assert.equal( 'WP_ENV_LIFECYCLE_SCRIPT_AFTER_START' in out, false );
	assert.deepEqual( ALLOWED_WP_ENV_VARS, [
		'WP_ENV_CORE',
		'WP_ENV_PHP_VERSION',
		'WP_ENV_PORT',
	] );
} );

test( 'readWcVersion parses the plugin header', () => {
	assert.equal(
		readWcVersion( ' * Plugin Name: WooCommerce\n * Version: 11.1.0-dev\n' ),
		'11.1.0-dev'
	);
} );

test( 'computeHash is stable and sensitive to each input', () => {
	const base = {
		configText: '{"port":8086}',
		overrideText: '',
		setupScriptText: 'echo provision',
		allowlistEnv: { WP_ENV_PHP_VERSION: '8.1' },
		wcVersion: '11.1.0-dev',
	};
	const h = computeHash( base );
	assert.match( h, /^[0-9a-f]{32}$/ );
	assert.equal( computeHash( base ), h ); // stable
	assert.notEqual( computeHash( { ...base, wcVersion: '11.2.0-dev' } ), h );
	assert.notEqual( computeHash( { ...base, setupScriptText: 'echo other' } ), h );
	assert.notEqual(
		computeHash( { ...base, allowlistEnv: { WP_ENV_PHP_VERSION: '8.2' } } ),
		h
	);
} );

test( 'writeState/readState round-trips', () => {
	const dir = mkdtempSync( join( tmpdir(), 'runenv-' ) );
	try {
		assert.equal( readState( dir ), null );
		writeState( dir, { hash: 'abc', port: 9001, snapshotCreatedAt: 5 } );
		assert.deepEqual( readState( dir ), {
			hash: 'abc',
			port: 9001,
			snapshotCreatedAt: 5,
		} );
	} finally {
		rmSync( dir, { recursive: true, force: true } );
	}
} );

test( 'decide picks rebuild vs fresh', () => {
	const state = { hash: 'h', port: 9001, snapshotCreatedAt: 1000 };
	const p = { state, currentHash: 'h', nowMs: 1000, maxAgeMs: MAX_AGE_MS, forceRebuild: false };
	assert.equal( decide( p ), 'fresh' );
	assert.equal( decide( { ...p, forceRebuild: true } ), 'rebuild' );
	assert.equal( decide( { ...p, state: null } ), 'rebuild' );
	assert.equal( decide( { ...p, currentHash: 'other' } ), 'rebuild' );
	assert.equal( decide( { ...p, nowMs: 1000 + MAX_AGE_MS + 1 } ), 'rebuild' );
} );

import { createServer } from 'node:http';
import { probeFreePort, isPortFree } from './run-env.mjs';

test( 'probeFreePort returns a usable port and isPortFree reflects binding', async () => {
	const port = await probeFreePort();
	assert.ok( port > 0 && port < 65536 );
	assert.equal( await isPortFree( port ), true );

	const server = createServer( () => {} );
	await new Promise( ( res ) => server.listen( port, res ) );
	try {
		assert.equal( await isPortFree( port ), false );
	} finally {
		await new Promise( ( res ) => server.close( res ) );
	}
} );

import { formatRunnerEnv } from './run-env.mjs';

test( 'formatRunnerEnv emits a BASE_URL line', () => {
	assert.equal( formatRunnerEnv( 9001 ), 'BASE_URL=http://localhost:9001\n' );
} );
