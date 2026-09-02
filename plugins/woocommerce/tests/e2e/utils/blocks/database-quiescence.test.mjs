/**
 * External dependencies
 */
import assert from 'node:assert/strict';
import { execFile, spawn } from 'node:child_process';
import { randomUUID } from 'node:crypto';
import { promisify } from 'node:util';
import { after, before, test } from 'node:test';

const execFileAsync = promisify( execFile );
const SNAPSHOT_COORDINATOR =
	'/var/www/html/wp-content/plugins/woocommerce/blocks-bin/playwright/database-snapshot.php';
const LOCK_FILE = '/var/www/html/.woocommerce-blocks-e2e-db.lock';
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
	return new URL(
		pathname,
		process.env.BASE_URL ||
			`http://localhost:${ process.env.WP_ENV_PORT || '8086' }`
	);
}

async function discoverContainer( service ) {
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

async function dockerExec( containerId, args, options = {} ) {
	return await execFileAsync( 'docker', [ 'exec', containerId, ...args ], {
		maxBuffer: 1024 * 1024 * 10,
		...options,
	} );
}

function startDockerExec( containerId, args ) {
	const child = spawn( 'docker', [ 'exec', containerId, ...args ], {
		stdio: [ 'ignore', 'pipe', 'pipe' ],
	} );
	let stdout = '';
	let stderr = '';

	child.stdout.setEncoding( 'utf8' );
	child.stderr.setEncoding( 'utf8' );
	child.stdout.on( 'data', ( chunk ) => {
		stdout += chunk;
	} );
	child.stderr.on( 'data', ( chunk ) => {
		stderr += chunk;
	} );

	const completion = new Promise( ( resolve, reject ) => {
		child.once( 'error', reject );
		child.once( 'close', ( code, signal ) => {
			if ( code === 0 ) {
				resolve( { stdout, stderr } );
				return;
			}

			const error = new Error(
				`docker exec exited with ${
					code ?? signal
				}: ${ stdout } ${ stderr }`
			);
			error.code = code;
			error.signal = signal;
			reject( error );
		} );
	} );
	void completion.catch( () => {} );

	return { child, completion };
}

function createLineReader( stream ) {
	let buffered = '';
	const lines = [];
	const waiters = [];

	stream.setEncoding( 'utf8' );
	stream.on( 'data', ( chunk ) => {
		buffered += chunk;
		const chunks = buffered.split( '\n' );
		buffered = chunks.pop();

		for ( const line of chunks ) {
			const waiter = waiters.shift();
			if ( waiter ) {
				waiter( line );
			} else {
				lines.push( line );
			}
		}
	} );

	return async () => {
		if ( lines.length ) {
			return lines.shift();
		}

		return await new Promise( ( resolve, reject ) => {
			const timeout = setTimeout( () => {
				reject( new Error( 'Timed out waiting for a lock event.' ) );
			}, 10_000 );

			waiters.push( ( line ) => {
				clearTimeout( timeout );
				resolve( line );
			} );
		} );
	};
}

async function writeFifo( fifo, value ) {
	await dockerExec( cliContainerId, [
		'php',
		'-r',
		'$handle=fopen($argv[1], "w"); fwrite($handle, $argv[2] . PHP_EOL); fclose($handle);',
		'--',
		fifo,
		value,
	] );
}

async function cleanupContainerOwners( root, pidFiles, processes ) {
	try {
		await dockerExec( cliContainerId, [
			'php',
			'-r',
			'$root=$argv[1]; foreach(array_slice($argv, 2) as $file){if(!is_file($file)){continue;} $pid=(int)trim(file_get_contents($file)); $command=@file_get_contents("/proc/" . $pid . "/cmdline"); if(false!==$command && false!==strpos($command, $root)){posix_kill($pid, 15);}}',
			'--',
			root,
			...pidFiles,
		] );
	} finally {
		await Promise.allSettled(
			processes.map( ( process ) => process.completion )
		);
		await dockerExec( cliContainerId, [ 'rm', '-rf', root ] );
	}
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

test(
	'request readers span PHP shutdown and writers reject new requests without queuing',
	{ timeout: 60_000 },
	async () => {
		for ( let iteration = 0; iteration < 2; iteration++ ) {
			const root = `/var/www/html/.woocommerce-blocks-e2e-quiescence-${
				process.pid
			}-${ randomUUID() }`;
			const eventFifo = `${ root }/events`;
			const readerReleaseFifo = `${ root }/reader-release`;
			const writerReleaseFifo = `${ root }/writer-release`;
			const eventReaderPid = `${ root }/event-reader.pid`;
			const writerPid = `${ root }/writer.pid`;
			const processes = [];

			await dockerExec( cliContainerId, [ 'mkdir', '-p', root ] );
			await dockerExec( cliContainerId, [
				'mkfifo',
				eventFifo,
				readerReleaseFifo,
				writerReleaseFifo,
			] );

			try {
				const eventReader = startDockerExec( cliContainerId, [
					'php',
					'-r',
					'file_put_contents($argv[1], getmypid()); $handle=fopen($argv[2], "r+"); for($i=0; $i<(int)$argv[3]; $i++){ $line=fgets($handle); if(false===$line){exit(71);} echo $line; flush(); }',
					'--',
					eventReaderPid,
					eventFifo,
					'5',
				] );
				processes.push( eventReader );
				const nextEvent = createLineReader( eventReader.child.stdout );

				const shutdownProbe = baseUrl( REQUEST_LOCK_PROBE_URL );
				shutdownProbe.searchParams.set( 'mode', 'shutdown' );
				shutdownProbe.searchParams.set( 'event_fifo', eventFifo );
				shutdownProbe.searchParams.set(
					'release_fifo',
					readerReleaseFifo
				);
				const readerResponse = fetch( shutdownProbe, {
					signal: AbortSignal.timeout( 20_000 ),
				} );

				assert.equal( await nextEvent(), 'reader-shutdown-entered' );

				const writer = startDockerExec( cliContainerId, [
					'php',
					'-r',
					'file_put_contents($argv[1], getmypid()); $write=function($value)use($argv){$handle=fopen($argv[2], "w"); fwrite($handle, $value . PHP_EOL); fclose($handle);}; $write("writer-requesting"); $lock=fopen($argv[3], "r+"); if(false===$lock || !flock($lock, LOCK_EX)){exit(75);} $write("writer-acquired"); $release=fopen($argv[4], "r"); $value=fgets($release); fclose($release); if("release-writer\n"!==$value){exit(71);} $write("writer-releasing");',
					'--',
					writerPid,
					eventFifo,
					LOCK_FILE,
					writerReleaseFifo,
				] );
				processes.push( writer );

				assert.equal( await nextEvent(), 'writer-requesting' );
				await writeFifo( readerReleaseFifo, 'release-reader' );
				assert.equal( await nextEvent(), 'reader-shutdown-leaving' );
				assert.equal( await nextEvent(), 'writer-acquired' );

				const blockedResponse = await fetch(
					baseUrl(
						`${ REQUEST_LOCK_PROBE_URL }?writer=${ iteration }`
					),
					{ signal: AbortSignal.timeout( 10_000 ) }
				);
				const blockedBody = await blockedResponse.text();
				assert.equal( blockedResponse.status, 503 );
				assert.equal(
					blockedBody,
					'WooCommerce Blocks E2E database snapshot in progress.\n'
				);
				assert.doesNotMatch(
					blockedBody,
					/WordPress database error|wp_/i
				);

				await writeFifo( writerReleaseFifo, 'release-writer' );
				assert.equal( await nextEvent(), 'writer-releasing' );
				await writer.completion;

				const completedReaderResponse = await readerResponse;
				assert.equal( completedReaderResponse.status, 200 );
				assert.equal(
					await completedReaderResponse.text(),
					'WooCommerce Blocks E2E shutdown probe body complete.\n'
				);
				await eventReader.completion;

				const resumedResponse = await fetch(
					baseUrl(
						`${ REQUEST_LOCK_PROBE_URL }?resumed=${ iteration }`
					),
					{ signal: AbortSignal.timeout( 10_000 ) }
				);
				assert.equal( resumedResponse.status, 200 );
				assert.equal(
					await resumedResponse.text(),
					'WooCommerce Blocks E2E database request lock active.\n'
				);
			} finally {
				await cleanupContainerOwners(
					root,
					[ eventReaderPid, writerPid ],
					processes
				);
			}
		}
	}
);

test(
	'an interrupted exclusive owner releases the request lock',
	{ timeout: 30_000 },
	async () => {
		const root = `/var/www/html/.woocommerce-blocks-e2e-quiescence-${
			process.pid
		}-${ randomUUID() }`;
		const eventFifo = `${ root }/events`;
		const writerReleaseFifo = `${ root }/writer-release`;
		const eventReaderPid = `${ root }/event-reader.pid`;
		const writerPid = `${ root }/writer.pid`;
		const processes = [];

		await dockerExec( cliContainerId, [ 'mkdir', '-p', root ] );
		await dockerExec( cliContainerId, [
			'mkfifo',
			eventFifo,
			writerReleaseFifo,
		] );

		try {
			const eventReader = startDockerExec( cliContainerId, [
				'php',
				'-r',
				'file_put_contents($argv[1], getmypid()); $handle=fopen($argv[2], "r+"); for($i=0; $i<2; $i++){echo fgets($handle); flush();}',
				'--',
				eventReaderPid,
				eventFifo,
			] );
			processes.push( eventReader );
			const nextEvent = createLineReader( eventReader.child.stdout );

			const writer = startDockerExec( cliContainerId, [
				'php',
				'-r',
				'file_put_contents($argv[1], getmypid()); $write=function($value)use($argv){$handle=fopen($argv[2], "w"); fwrite($handle, $value . PHP_EOL); fclose($handle);}; $write("writer-requesting"); $lock=fopen($argv[3], "r+"); if(false===$lock || !flock($lock, LOCK_EX)){exit(75);} $write("writer-acquired"); $release=fopen($argv[4], "r"); fgets($release);',
				'--',
				writerPid,
				eventFifo,
				LOCK_FILE,
				writerReleaseFifo,
			] );
			processes.push( writer );

			assert.equal( await nextEvent(), 'writer-requesting' );
			assert.equal( await nextEvent(), 'writer-acquired' );
			await eventReader.completion;

			const blockedResponse = await fetch(
				baseUrl( `${ REQUEST_LOCK_PROBE_URL }?interrupted=blocked` ),
				{ signal: AbortSignal.timeout( 10_000 ) }
			);
			assert.equal( blockedResponse.status, 503 );

			await dockerExec( cliContainerId, [
				'php',
				'-r',
				'$pid=(int)trim(file_get_contents($argv[1])); $command=file_get_contents("/proc/" . $pid . "/cmdline"); exit(false!==strpos($command, $argv[2]) && posix_kill($pid, 15) ? 0 : 1);',
				'--',
				writerPid,
				root,
			] );
			await assert.rejects( writer.completion );

			const resumedResponse = await fetch(
				baseUrl( `${ REQUEST_LOCK_PROBE_URL }?interrupted=resumed` ),
				{ signal: AbortSignal.timeout( 10_000 ) }
			);
			assert.equal( resumedResponse.status, 200 );
			assert.equal(
				await resumedResponse.text(),
				'WooCommerce Blocks E2E database request lock active.\n'
			);
		} finally {
			await cleanupContainerOwners(
				root,
				[ eventReaderPid, writerPid ],
				processes
			);
		}
	}
);

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
