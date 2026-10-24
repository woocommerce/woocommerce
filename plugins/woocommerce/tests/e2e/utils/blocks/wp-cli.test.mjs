/**
 * External dependencies
 */
import assert from 'node:assert/strict';
import { mkdtemp, readFile, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { test } from 'node:test';

/**
 * Internal dependencies
 */
import { createBlocksCli } from './wp-cli.ts';

const CLI_CONTAINER_ID = '0123456789ab';
const DISCOVERY_CALL = [
	'npm',
	[ 'run', 'wp-env:e2e', 'run', 'cli', '--', 'printenv', 'HOSTNAME' ],
];
const USER = `${ process.getuid?.() ?? 1000 }:${ process.getgid?.() ?? 1000 }`;

const restoreCall = ( databaseFile ) => [
	'docker',
	[
		'exec',
		'--workdir',
		'/var/www/html',
		CLI_CONTAINER_ID,
		'sh',
		'-c',
		'wp db reset --yes && wp db import "$1"',
		'restore-blocks-database',
		databaseFile,
	],
];

const wpCLICall = ( command ) => [
	'sh',
	[
		'-c',
		`docker exec --workdir /var/www/html --user ${ USER } ${ CLI_CONTAINER_ID } wp ${ command }`,
	],
];

const missingContainerIdFile = async () =>
	path.join(
		await mkdtemp( path.join( tmpdir(), 'blocks-cli-' ) ),
		'state',
		'blocks-cli-container-id'
	);

const createRecorder = () => {
	const calls = [];
	const runCommand = async ( executable, args ) => {
		calls.push( [ executable, args ] );

		if ( executable === 'npm' ) {
			return {
				stdout: `npm output\n${ CLI_CONTAINER_ID }\n`,
				stderr: '',
			};
		}

		return { stdout: '', stderr: '' };
	};

	return { calls, runCommand };
};

test( 'uses the saved CLI container ID without starting wp-env', async () => {
	const containerIdFile = await missingContainerIdFile();
	const setup = createRecorder();
	await createBlocksCli(
		setup.runCommand,
		containerIdFile
	).saveCliContainerId();

	assert.deepEqual( setup.calls, [ DISCOVERY_CALL ] );
	assert.equal( await readFile( containerIdFile, 'utf8' ), CLI_CONTAINER_ID );

	const worker = createRecorder();
	const cli = createBlocksCli( worker.runCommand, containerIdFile );

	await cli.wpCLI( 'core version' );
	await cli.restoreDatabase( '/tmp/first snapshot.sql' );
	await cli.restoreDatabase( '/tmp/second.sql' );

	assert.deepEqual( worker.calls, [
		wpCLICall( 'core version' ),
		restoreCall( '/tmp/first snapshot.sql' ),
		restoreCall( '/tmp/second.sql' ),
	] );
} );

test( 'finds the CLI container through wp-env once when no ID is saved', async () => {
	const { calls, runCommand } = createRecorder();
	const cli = createBlocksCli( runCommand, await missingContainerIdFile() );

	await cli.restoreDatabase( '/tmp/snapshot.sql' );
	await cli.wpCLI( 'option get siteurl' );

	assert.deepEqual( calls, [
		DISCOVERY_CALL,
		restoreCall( '/tmp/snapshot.sql' ),
		wpCLICall( 'option get siteurl' ),
	] );
} );

test( 'finds the CLI container through wp-env when the saved ID is invalid', async () => {
	const containerIdFile = await missingContainerIdFile();
	const { calls, runCommand } = createRecorder();
	await createBlocksCli( runCommand, containerIdFile ).saveCliContainerId();
	await writeFile( containerIdFile, '' );
	calls.length = 0;

	await createBlocksCli( runCommand, containerIdFile ).wpCLI( 'core version' );

	assert.deepEqual( calls, [ DISCOVERY_CALL, wpCLICall( 'core version' ) ] );
} );

test( 'setup replaces a stale saved ID', async () => {
	const containerIdFile = await missingContainerIdFile();
	const { calls, runCommand } = createRecorder();
	await createBlocksCli( runCommand, containerIdFile ).saveCliContainerId();
	await writeFile( containerIdFile, 'stale' );

	await createBlocksCli( runCommand, containerIdFile ).saveCliContainerId();

	assert.equal( calls.length, 2 );
	assert.equal( await readFile( containerIdFile, 'utf8' ), CLI_CONTAINER_ID );
} );

test( 'fails before restoring when CLI container discovery is malformed', async () => {
	const calls = [];
	const runCommand = async ( executable, args ) => {
		calls.push( [ executable, args ] );
		return { stdout: 'npm output without a container ID\n', stderr: '' };
	};
	const cli = createBlocksCli( runCommand, await missingContainerIdFile() );

	await assert.rejects(
		cli.restoreDatabase( '/tmp/snapshot.sql' ),
		/Failed to determine the Blocks E2E CLI container ID/
	);
	assert.equal( calls.length, 1 );
} );

test( 'retries CLI container discovery after a failed attempt', async () => {
	const calls = [];
	const runCommand = async ( executable, args ) => {
		calls.push( [ executable, args ] );

		if ( executable === 'npm' ) {
			if ( calls.length === 1 ) {
				throw new Error( 'transient wp-env failure' );
			}

			return {
				stdout: `npm output\n${ CLI_CONTAINER_ID }\n`,
				stderr: '',
			};
		}

		return { stdout: '', stderr: '' };
	};
	const cli = createBlocksCli( runCommand, await missingContainerIdFile() );

	await assert.rejects(
		cli.restoreDatabase( '/tmp/snapshot.sql' ),
		/transient wp-env failure/
	);

	await cli.restoreDatabase( '/tmp/snapshot.sql' );

	assert.deepEqual(
		calls.map( ( [ executable ] ) => executable ),
		[ 'npm', 'npm', 'docker' ]
	);
} );
