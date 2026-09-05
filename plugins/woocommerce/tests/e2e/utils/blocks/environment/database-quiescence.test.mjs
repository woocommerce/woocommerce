/**
 * External dependencies
 */
import assert from 'node:assert/strict';
import { execFile } from 'node:child_process';
import { randomUUID } from 'node:crypto';
import { setTimeout } from 'node:timers/promises';
import { promisify } from 'node:util';
import { after, before, test } from 'node:test';

/**
 * Internal dependencies
 */
import { BASE_URL } from '../constants.ts';

const execFileAsync = promisify( execFile );
const SNAPSHOT_COORDINATOR =
	'/var/www/html/wp-content/plugins/woocommerce/blocks-bin/playwright/database-snapshot.php';
const LOCK_FILE = '/var/www/html/.woocommerce-blocks-e2e-db.lock';
const HTACCESS_LOCK_BLOCK_SCRIPT =
	'/var/www/html/wp-content/plugins/woocommerce/blocks-bin/playwright/htaccess-lock-block.php';
const HTACCESS_FILE = '/var/www/html/.htaccess';
const REQUEST_LOCK_PROBE_URL =
	'/wp-content/plugins/woocommerce/blocks-bin/playwright/request-lock-probe.php';
const REQUEST_LOCK_BLOCK = `# BEGIN WooCommerce Blocks E2E DB Lock
php_value auto_prepend_file /var/www/html/wp-content/plugins/woocommerce/blocks-bin/playwright/request-lock.php
# END WooCommerce Blocks E2E DB Lock
`;
const CONTAINER_PATH =
	'/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin';
const FAKE_WP = `#!/usr/bin/env php
<?php
$arguments = array_slice( $argv, 1 );
file_put_contents(
	getenv( 'WC_E2E_CALL_LOG' ),
	json_encode( $arguments ) . PHP_EOL,
	FILE_APPEND | LOCK_EX
);

if ( false !== getenv( 'WC_E2E_STDOUT' ) ) {
	fwrite( STDOUT, getenv( 'WC_E2E_STDOUT' ) );
}

if ( false !== getenv( 'WC_E2E_STDERR' ) ) {
	fwrite( STDERR, getenv( 'WC_E2E_STDERR' ) );
}

if ( getenv( 'WC_E2E_FAIL_COMMAND' ) === implode( ' ', array_slice( $arguments, 0, 2 ) ) ) {
	exit( (int) getenv( 'WC_E2E_FAIL_STATUS' ) );
}
`;

let cliContainerId;
let wordpressContainerId;
let testRoot;
let callLog;

function baseUrl( pathname ) {
	return new URL( pathname, BASE_URL );
}

async function discoverContainerOnce( service ) {
	const { stdout, stderr } = await execFileAsync( 'npm', [
		'run',
		'wp-env:e2e',
		'run',
		service,
		'--',
		'printenv',
		'HOSTNAME',
	] );
	const containerId = stdout.match( /^(?<containerId>[a-f0-9]{12,64})\r?$/m )
		?.groups?.containerId;

	if ( ! containerId ) {
		throw new Error(
			`Failed to determine the ${ service } container ID: ${ stdout } ${ stderr }`
		);
	}

	return containerId;
}

/**
 * Resolves a running container's ID through wp-env, retrying a failed attempt.
 *
 * wp-env reports every failure to read its own cache file as "Environment not
 * initialized", so a transient read against a healthy environment is
 * indistinguishable from a genuinely stopped one. `wp-cli.ts` absorbs the same
 * failure by discarding a rejected discovery so the next snapshot operation
 * retries; without an equivalent here one blip fails the whole file.
 *
 * @param {string} service The wp-env service name, such as `cli` or `wordpress`.
 */
async function discoverContainer( service ) {
	let lastError;

	for ( let attempt = 1; attempt <= 3; attempt++ ) {
		if ( attempt > 1 ) {
			await setTimeout( ( attempt - 1 ) * 1000 );
		}

		try {
			return await discoverContainerOnce( service );
		} catch ( error ) {
			lastError = error;
		}
	}

	throw lastError;
}

async function dockerExec( containerId, args ) {
	return await execFileAsync( 'docker', [ 'exec', containerId, ...args ], {
		maxBuffer: 1024 * 1024 * 10,
	} );
}

async function readCalls() {
	const { stdout } = await dockerExec( cliContainerId, [
		'php',
		'-r',
		'echo is_file($argv[1]) ? file_get_contents($argv[1]) : "";',
		'--',
		callLog,
	] );

	return stdout
		.trim()
		.split( '\n' )
		.filter( Boolean )
		.map( ( line ) => JSON.parse( line ) );
}

async function clearCalls() {
	await dockerExec( cliContainerId, [ 'rm', '-f', callLog ] );
}

async function runCoordinator( operation, snapshotPath, environment = {} ) {
	const environmentArgs = Object.entries( environment ).flatMap(
		( [ name, value ] ) => [ '-e', `${ name }=${ value }` ]
	);

	return await execFileAsync( 'docker', [
		'exec',
		'--workdir',
		'/var/www/html',
		'-e',
		`PATH=${ testRoot }:${ CONTAINER_PATH }`,
		'-e',
		`WC_E2E_CALL_LOG=${ callLog }`,
		...environmentArgs,
		cliContainerId,
		'php',
		SNAPSHOT_COORDINATOR,
		operation,
		snapshotPath,
	] );
}

before( async () => {
	// Sequential rather than concurrent: two wp-env processes starting together
	// buy nothing here and only add contention to the step that already proved
	// fragile.
	cliContainerId = await discoverContainer( 'cli' );
	wordpressContainerId = await discoverContainer( 'wordpress' );
	testRoot = `/var/www/html/.woocommerce-blocks-e2e-quiescence-${
		process.pid
	}-${ randomUUID() }`;
	callLog = `${ testRoot }/wp-calls.jsonl`;

	await dockerExec( cliContainerId, [
		'php',
		'-r',
		'$root=$argv[1]; mkdir($root, 0777, true); file_put_contents($root . "/wp", base64_decode($argv[2])); chmod($root . "/wp", 0755);',
		'--',
		testRoot,
		Buffer.from( FAKE_WP ).toString( 'base64' ),
	] );
} );

after( async () => {
	if ( cliContainerId && testRoot ) {
		await dockerExec( cliContainerId, [ 'rm', '-rf', testRoot ] );
	}
} );

test( 'the selected Blocks profile owns one shared request-lock inode and prepend block', async () => {
	const { stdout: htaccess } = await dockerExec( cliContainerId, [
		'cat',
		HTACCESS_FILE,
	] );
	const cliLock = await dockerExec( cliContainerId, [
		'stat',
		'-Lc',
		'%d:%i:%a',
		LOCK_FILE,
	] );
	const wordpressLock = await dockerExec( wordpressContainerId, [
		'stat',
		'-Lc',
		'%d:%i:%a',
		LOCK_FILE,
	] );

	assert.equal(
		htaccess.split( '# BEGIN WooCommerce Blocks E2E DB Lock' ).length - 1,
		1
	);
	assert.equal(
		htaccess.split( '# END WooCommerce Blocks E2E DB Lock' ).length - 1,
		1
	);
	assert.equal(
		htaccess.split(
			'php_value auto_prepend_file /var/www/html/wp-content/plugins/woocommerce/blocks-bin/playwright/request-lock.php'
		).length - 1,
		1
	);
	assert.ok( htaccess.includes( REQUEST_LOCK_BLOCK ) );
	assert.equal( cliLock.stdout.trim(), wordpressLock.stdout.trim() );
	assert.match( cliLock.stdout.trim(), /^\d+:\d+:666$/ );

	await execFileAsync( 'npm', [
		'run',
		'wp-env:e2e',
		'run',
		'cli',
		'--',
		'wp',
		'rewrite',
		'flush',
		'--hard',
	] );
	const { stdout: rewrittenHtaccess } = await dockerExec( cliContainerId, [
		'cat',
		HTACCESS_FILE,
	] );
	const rewrittenLock = await dockerExec( cliContainerId, [
		'stat',
		'-Lc',
		'%d:%i:%a',
		LOCK_FILE,
	] );
	assert.ok( rewrittenHtaccess.includes( REQUEST_LOCK_BLOCK ) );
	assert.equal( rewrittenLock.stdout.trim(), cliLock.stdout.trim() );

	const response = await fetch( baseUrl( REQUEST_LOCK_PROBE_URL ), {
		signal: AbortSignal.timeout( 10_000 ),
	} );
	assert.equal( response.status, 200 );
	assert.equal(
		await response.text(),
		'WooCommerce Blocks E2E database request lock active.\n'
	);
} );

test( 'a held exclusive lock rejects requests with 503 until it is released', async () => {
	// Hold the lock the coordinator takes, from the CLI container, and fetch the
	// probe while holding it so Apache's PHP is seen contending on the same lock.
	const { stdout } = await dockerExec( cliContainerId, [
		'php',
		'-r',
		'$lock=fopen($argv[1], "r+"); if(false===$lock || !flock($lock, LOCK_EX|LOCK_NB)){exit(75);} $context=stream_context_create(array("http"=>array("ignore_errors"=>true, "timeout"=>10))); $body=file_get_contents($argv[2], false, $context); echo $http_response_header[0], PHP_EOL, $body;',
		'--',
		LOCK_FILE,
		`http://wordpress${ REQUEST_LOCK_PROBE_URL }`,
	] );
	assert.match( stdout, /^HTTP\/1\.[01] 503 / );
	assert.ok(
		stdout.includes(
			'WooCommerce Blocks E2E database snapshot in progress.'
		)
	);

	// The holder has exited, so the lock is released.
	const response = await fetch( baseUrl( REQUEST_LOCK_PROBE_URL ), {
		signal: AbortSignal.timeout( 10_000 ),
	} );
	assert.equal( response.status, 200 );
} );

test( 'htaccess-lock-block.php removes any marker block and installs exactly one', async () => {
	const root = `${ testRoot }/htaccess`;
	const htaccess = `${ root }/.htaccess`;
	const script = `${ root }/lock-block.php`;
	const base = '# BEGIN WordPress\nRewriteEngine On\n# END WordPress\n';
	const staleBlock =
		'# BEGIN WooCommerce Blocks E2E DB Lock\nphp_value auto_prepend_file /an/older/path/request-lock.php\n# END WooCommerce Blocks E2E DB Lock\n';
	const writeHtaccess = ( contents ) =>
		dockerExec( cliContainerId, [
			'php',
			'-r',
			'file_put_contents($argv[1], $argv[2]);',
			'--',
			htaccess,
			contents,
		] );
	const readHtaccess = async () =>
		( await dockerExec( cliContainerId, [ 'cat', htaccess ] ) ).stdout;
	const run = ( mode ) =>
		dockerExec( cliContainerId, [ 'php', script, mode ] );

	// Point a copy of the script at a scratch .htaccess so the live one is untouched.
	await dockerExec( cliContainerId, [
		'sh',
		'-c',
		'mkdir -p "$1" && sed "s#/var/www/html/.htaccess#$1/.htaccess#" "$2" > "$1/lock-block.php"',
		'--',
		root,
		HTACCESS_LOCK_BLOCK_SCRIPT,
	] );

	// A block written by an older revision, present twice, is removed by its markers.
	await writeHtaccess( base + staleBlock + staleBlock );
	await run( 'remove' );
	assert.equal( await readHtaccess(), base );

	// Install replaces a stale block rather than adding a second, and is idempotent.
	await writeHtaccess( base + staleBlock );
	await run( 'install' );
	await run( 'install' );
	assert.equal( await readHtaccess(), base + REQUEST_LOCK_BLOCK );

	// Remove restores the original file, and a missing file counts as removed.
	await run( 'remove' );
	assert.equal( await readHtaccess(), base );
	await dockerExec( cliContainerId, [ 'rm', htaccess ] );
	await run( 'remove' );
} );

test( 'the PHP coordinator owns import, restore, and export child lifecycles', async () => {
	const sideEffectPath = `${ testRoot }/shell-side-effect`;
	const snapshotPath = `/var/www/html/a snapshot; $(touch ${ sideEffectPath }) * "double" 'single'.sql`;

	await runCoordinator( 'import', snapshotPath );
	assert.deepEqual( await readCalls(), [ [ 'db', 'import', snapshotPath ] ] );

	await clearCalls();
	await runCoordinator( 'restore', snapshotPath );
	assert.deepEqual( await readCalls(), [
		[ 'db', 'reset', '--yes' ],
		[ 'db', 'import', snapshotPath ],
	] );

	await clearCalls();
	await runCoordinator( 'export', snapshotPath );
	assert.deepEqual( await readCalls(), [ [ 'db', 'export', snapshotPath ] ] );

	await dockerExec( cliContainerId, [
		'php',
		'-r',
		'exit(file_exists($argv[1]) ? 1 : 0);',
		'--',
		sideEffectPath,
	] );
} );

test( 'the PHP coordinator stops on and propagates the first child failure', async () => {
	await clearCalls();

	await assert.rejects(
		runCoordinator( 'restore', '/var/www/html/snapshot.sql', {
			WC_E2E_FAIL_COMMAND: 'db reset',
			WC_E2E_FAIL_STATUS: '17',
		} ),
		( error ) => error.code === 17
	);

	assert.deepEqual( await readCalls(), [ [ 'db', 'reset', '--yes' ] ] );

	const response = await fetch( baseUrl( REQUEST_LOCK_PROBE_URL ), {
		signal: AbortSignal.timeout( 10_000 ),
	} );
	assert.equal( response.status, 200 );
} );

test( 'the PHP coordinator propagates import failure output and releases its lock', async () => {
	await clearCalls();

	await assert.rejects(
		runCoordinator( 'restore', '/var/www/html/snapshot.sql', {
			WC_E2E_FAIL_COMMAND: 'db import',
			WC_E2E_FAIL_STATUS: '23',
			WC_E2E_STDOUT: 'import stdout\n',
			WC_E2E_STDERR: 'import stderr\n',
		} ),
		( error ) => {
			assert.equal( error.code, 23 );
			assert.equal( error.stdout, 'import stdout\nimport stdout\n' );
			assert.equal( error.stderr, 'import stderr\nimport stderr\n' );
			return true;
		}
	);

	assert.deepEqual( await readCalls(), [
		[ 'db', 'reset', '--yes' ],
		[ 'db', 'import', '/var/www/html/snapshot.sql' ],
	] );

	const response = await fetch( baseUrl( REQUEST_LOCK_PROBE_URL ), {
		signal: AbortSignal.timeout( 10_000 ),
	} );
	assert.equal( response.status, 200 );
} );
