/**
 * External dependencies
 */
import assert from 'node:assert/strict';
import { test } from 'node:test';

/**
 * Internal dependencies
 */
import { createBlocksDatabaseRestorer } from './wp-cli.ts';

const CLI_CONTAINER_ID = '0123456789ab';

test( 'reuses the discovered CLI container across database restores', async () => {
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
	const restoreDatabase = createBlocksDatabaseRestorer( runCommand );

	await restoreDatabase( '/tmp/first snapshot.sql' );
	await restoreDatabase( '/tmp/second.sql' );

	assert.deepEqual( calls, [
		[
			'npm',
			[ 'run', 'wp-env:e2e', 'run', 'cli', '--', 'printenv', 'HOSTNAME' ],
		],
		[
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
				'/tmp/first snapshot.sql',
			],
		],
		[
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
				'/tmp/second.sql',
			],
		],
	] );
} );

test( 'fails before restoring when CLI container discovery is malformed', async () => {
	const calls = [];
	const runCommand = async ( executable, args ) => {
		calls.push( [ executable, args ] );
		return { stdout: 'npm output without a container ID\n', stderr: '' };
	};
	const restoreDatabase = createBlocksDatabaseRestorer( runCommand );

	await assert.rejects(
		restoreDatabase( '/tmp/snapshot.sql' ),
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
	const restoreDatabase = createBlocksDatabaseRestorer( runCommand );

	await assert.rejects(
		restoreDatabase( '/tmp/snapshot.sql' ),
		/transient wp-env failure/
	);

	await restoreDatabase( '/tmp/snapshot.sql' );

	assert.deepEqual(
		calls.map( ( [ executable ] ) => executable ),
		[ 'npm', 'npm', 'docker' ]
	);
} );
