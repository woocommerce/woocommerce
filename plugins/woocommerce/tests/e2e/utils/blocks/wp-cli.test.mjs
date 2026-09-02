/**
 * External dependencies
 */
import assert from 'node:assert/strict';
import { test } from 'node:test';

/**
 * Internal dependencies
 */
import { createBlocksDatabaseSnapshotCoordinator } from './wp-cli.ts';

const CLI_CONTAINER_ID = '0123456789ab';
const SNAPSHOT_COORDINATOR =
	'/var/www/html/wp-content/plugins/woocommerce/blocks-bin/playwright/database-snapshot.php';

function successfulCommand( calls ) {
	return async ( executable, args ) => {
		calls.push( [ executable, args ] );

		if ( executable === 'npm' ) {
			return {
				stdout: `npm output\n${ CLI_CONTAINER_ID }\n`,
				stderr: '',
			};
		}

		return { stdout: '', stderr: '' };
	};
}

test( 'reuses one discovered CLI container for one direct exec per snapshot operation', async () => {
	const calls = [];
	const coordinateSnapshot = createBlocksDatabaseSnapshotCoordinator(
		successfulCommand( calls )
	);

	await coordinateSnapshot( 'import', '/tmp/first snapshot.sql' );
	await coordinateSnapshot( 'restore', '/tmp/second.sql' );
	await coordinateSnapshot( 'export', '/tmp/final snapshot.sql' );

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
				'php',
				SNAPSHOT_COORDINATOR,
				'import',
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
				'php',
				SNAPSHOT_COORDINATOR,
				'restore',
				'/tmp/second.sql',
			],
		],
		[
			'docker',
			[
				'exec',
				'--workdir',
				'/var/www/html',
				CLI_CONTAINER_ID,
				'php',
				SNAPSHOT_COORDINATOR,
				'export',
				'/tmp/final snapshot.sql',
			],
		],
	] );
} );

test( 'fails before a snapshot operation when CLI container discovery is malformed', async () => {
	const calls = [];
	const runCommand = async ( executable, args ) => {
		calls.push( [ executable, args ] );
		return { stdout: 'npm output without a container ID\n', stderr: '' };
	};
	const coordinateSnapshot =
		createBlocksDatabaseSnapshotCoordinator( runCommand );

	await assert.rejects(
		coordinateSnapshot( 'restore', '/tmp/snapshot.sql' ),
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
	const coordinateSnapshot =
		createBlocksDatabaseSnapshotCoordinator( runCommand );

	await assert.rejects(
		coordinateSnapshot( 'restore', '/tmp/snapshot.sql' ),
		/transient wp-env failure/
	);

	await coordinateSnapshot( 'restore', '/tmp/snapshot.sql' );

	assert.deepEqual(
		calls.map( ( [ executable ] ) => executable ),
		[ 'npm', 'npm', 'docker' ]
	);
} );

test( 'propagates the coordinator failure without another command or rediscovery', async () => {
	const calls = [];
	const coordinatorFailure = Object.assign(
		new Error( 'snapshot coordinator failed' ),
		{ code: 17 }
	);
	const runCommand = async ( executable, args ) => {
		calls.push( [ executable, args ] );

		if ( executable === 'npm' ) {
			return {
				stdout: `${ CLI_CONTAINER_ID }\n`,
				stderr: '',
			};
		}

		throw coordinatorFailure;
	};
	const coordinateSnapshot =
		createBlocksDatabaseSnapshotCoordinator( runCommand );

	await assert.rejects(
		coordinateSnapshot( 'restore', '/tmp/snapshot.sql' ),
		( error ) => error === coordinatorFailure
	);

	assert.equal( calls.length, 2 );
	assert.equal( calls[ 1 ][ 1 ].at( -2 ), 'restore' );
} );
