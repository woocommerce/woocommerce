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

async function runCommand( executable: string, args: string[] ) {
	return await execFilePromisified( executable, args );
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
 * Creates a database restore function that resolves the Blocks E2E CLI
 * container once and reuses it for subsequent restores.
 */
export function createBlocksDatabaseRestorer( execute: RunCommand ) {
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

	return async ( databaseFile: string ) => {
		const cliContainerId = await getCliContainerId();

		return await execute( 'docker', [
			'exec',
			'--workdir',
			'/var/www/html',
			cliContainerId,
			'sh',
			'-c',
			'wp db reset --yes && wp db import "$1"',
			'restore-blocks-database',
			databaseFile,
		] );
	};
}

const restoreDatabase = createBlocksDatabaseRestorer( runCommand );

/**
 * Resets the Blocks E2E database and imports its snapshot through the existing
 * CLI container.
 */
export async function restoreBlocksDatabase( databaseFile: string ) {
	return await restoreDatabase( databaseFile );
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
