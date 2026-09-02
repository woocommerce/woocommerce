/**
 * External dependencies
 */
import { promisify } from 'util';
import { exec, execFile } from 'child_process';

const execPromisified = promisify( exec );
const execFilePromisified = promisify( execFile );

type CommandResult = {
	stdout: string;
	stderr: string;
};

type RunCommand = (
	executable: string,
	args: string[]
) => Promise< CommandResult >;

type DatabaseSnapshotOperation = 'import' | 'restore' | 'export';

const DATABASE_SNAPSHOT_COORDINATOR =
	'/var/www/html/wp-content/plugins/woocommerce/blocks-bin/playwright/database-snapshot.php';

async function runCommand( executable: string, args: string[] ) {
	// The coordinator bounds its own lock wait at 60s; this backstops a hung docker exec.
	return await execFilePromisified( executable, args, { timeout: 120_000 } );
}

/**
 * Runs a WP-CLI command inside the single-container E2E environment's `cli`
 * container (started via `wp-env --config .wp-env.e2e.json`).
 */
export async function wpCLI( command: string ) {
	return await execPromisified(
		'npm run wp-env:e2e run cli -- wp ' + command
	);
}

/**
 * Creates a database snapshot coordinator that resolves the Blocks E2E CLI
 * container once and reuses it for subsequent operations.
 */
export function createBlocksDatabaseSnapshotCoordinator( execute: RunCommand ) {
	let cliContainerIdPromise: Promise< string > | undefined;

	const getCliContainerId = async () => {
		if ( ! cliContainerIdPromise ) {
			cliContainerIdPromise = execute( 'npm', [
				'run',
				'wp-env:e2e',
				'run',
				'cli',
				'--',
				'printenv',
				'HOSTNAME',
			] ).then( ( { stdout, stderr } ) => {
				// Match a 12 to 64 character hex string (Docker container ID) on its own line,
				// optionally followed by a carriage return.
				const cliContainerId = stdout.match(
					/^(?<containerId>[a-f0-9]{12,64})\r?$/m
				)?.groups?.containerId;

				if ( ! cliContainerId ) {
					throw new Error(
						`Failed to determine the Blocks E2E CLI container ID: ${ stdout } ${ stderr }`
					);
				}

				return cliContainerId;
			} );

			// Drop failed discoveries from the cache so the next restore
			// retries instead of reusing the rejection forever.
			cliContainerIdPromise.catch( () => {
				cliContainerIdPromise = undefined;
			} );
		}

		return await cliContainerIdPromise;
	};

	return async (
		operation: DatabaseSnapshotOperation,
		databaseFile: string
	) => {
		const cliContainerId = await getCliContainerId();

		return await execute( 'docker', [
			'exec',
			'--workdir',
			'/var/www/html',
			cliContainerId,
			'php',
			DATABASE_SNAPSHOT_COORDINATOR,
			operation,
			databaseFile,
		] );
	};
}

const coordinateDatabaseSnapshot =
	createBlocksDatabaseSnapshotCoordinator( runCommand );

/**
 * Imports a Blocks E2E database snapshot through the existing CLI container.
 */
export async function importBlocksDatabase( databaseFile: string ) {
	return await coordinateDatabaseSnapshot( 'import', databaseFile );
}

/**
 * Resets the Blocks E2E database and imports its snapshot through the existing
 * CLI container.
 */
export async function restoreBlocksDatabase( databaseFile: string ) {
	return await coordinateDatabaseSnapshot( 'restore', databaseFile );
}

/**
 * Exports a Blocks E2E database snapshot through the existing CLI container.
 */
export async function exportBlocksDatabase( databaseFile: string ) {
	return await coordinateDatabaseSnapshot( 'export', databaseFile );
}

/**
 * Returns the ID of the post with the given slug, throwing if none is found.
 *
 * @param slug     The post slug (`post_name`), not the title.
 * @param postType The post type to search. Defaults to `product`.
 */
export async function getPostIdBySlug( slug: string, postType = 'product' ) {
	const result = await wpCLI(
		`post list --post_type="${ postType }" --name="${ slug }" --field=ID`
	);
	// Extract just the numeric ID from output (npm adds prefix lines to stdout).
	const postId = result.stdout.match( /^(\d+)\r?$/m )?.[ 1 ];
	if ( ! postId ) {
		throw new Error(
			`Failed to find a "${ postType }" post with slug "${ slug }" via WP-CLI: ${ result.stdout } ${ result.stderr }`
		);
	}
	return postId;
}
