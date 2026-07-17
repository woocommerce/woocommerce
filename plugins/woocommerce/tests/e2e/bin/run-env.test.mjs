import { test } from 'node:test';
import assert from 'node:assert/strict';
import { parseArgs, isCi, sanitizeEnv, ALLOWED_WP_ENV_VARS } from './run-env.mjs';

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
