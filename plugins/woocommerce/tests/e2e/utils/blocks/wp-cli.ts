/**
 * External dependencies
 */
import { promisify } from 'util';
import { execFile } from 'child_process';
import { mkdir, readFile, writeFile } from 'fs/promises';
import path from 'path';

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
 * Runs WP-CLI through `docker exec` in the Blocks E2E CLI container.
 *
 * Overlapping wp-env commands can corrupt its cache file and break every later wp-env command, so parallel workers read the container ID that `blocks setup` saved to `containerIdFile`. They fall back to wp-env only when that file is missing or invalid. After recreating the environment, run `blocks setup` again to save the new ID.
 */
export function createBlocksCli( execute: RunCommand, containerIdFile: string ) {
	let cliContainerIdPromise: Promise< string > | undefined;

	const discoverCliContainerId = async () => {
		const { stdout, stderr } = await execute( 'npm', [
			'run',
			'wp-env:e2e',
			'run',
			'cli',
			'--',
			'printenv',
			'HOSTNAME',
		] );
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
	};

	const cache = ( promise: Promise< string > ) => {
		cliContainerIdPromise = promise;
		// Drop failed lookups from the cache so the next call retries
		// instead of reusing the rejection forever.
		promise.catch( () => {
			cliContainerIdPromise = undefined;
		} );
		return promise;
	};

	const getCliContainerId = async () =>
		await ( cliContainerIdPromise ??
			cache(
				readFile( containerIdFile, 'utf8' ).then( ( savedId ) => {
					const cliContainerId = savedId.trim();
					return /^[a-f0-9]{12,64}$/.test( cliContainerId )
						? cliContainerId
						: discoverCliContainerId();
				}, discoverCliContainerId )
			) );

	return {
		async saveCliContainerId() {
			const cliContainerId = await cache( discoverCliContainerId() );
			await mkdir( path.dirname( containerIdFile ), { recursive: true } );
			await writeFile( containerIdFile, cliContainerId );
		},

		async wpCLI( command: string ) {
			const cliContainerId = await getCliContainerId();
			const user = `${ process.getuid?.() ?? 1000 }:${
				process.getgid?.() ?? 1000
			}`;

			// Let the host shell parse `command`, as it did when this ran through wp-env.
			return await execute( 'sh', [
				'-c',
				`docker exec --workdir /var/www/html --user ${ user } ${ cliContainerId } wp ${ command }`,
			] );
		},

		async restoreDatabase( databaseFile: string ) {
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
		},
	};
}

// Relative to `plugins/woocommerce`, where wp-env and Playwright run.
const blocksCli = createBlocksCli(
	runCommand,
	path.resolve(
		process.env.WP_ARTIFACTS_PATH || 'tests/e2e/.state',
		'blocks-cli-container-id'
	)
);

/**
 * Finds the Blocks E2E CLI container through wp-env and saves its ID for the test workers. Call it only from `blocks setup`, which runs before the workers start.
 */
export async function saveBlocksCliContainerId() {
	return await blocksCli.saveCliContainerId();
}

/**
 * Runs a WP-CLI command inside the single-container E2E environment's `cli`
 * container (started via `wp-env --config .wp-env.e2e.json`).
 */
export async function wpCLI( command: string ) {
	return await blocksCli.wpCLI( command );
}

/**
 * Resets the Blocks E2E database and imports its snapshot through the existing
 * CLI container.
 */
export async function restoreBlocksDatabase( databaseFile: string ) {
	return await blocksCli.restoreDatabase( databaseFile );
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
	const postId = result.stdout.match( /^(\d+)\r?$/m )?.[ 1 ];
	if ( ! postId ) {
		throw new Error(
			`Failed to find a "${ postType }" post with slug "${ slug }" via WP-CLI: ${ result.stdout } ${ result.stderr }`
		);
	}
	return postId;
}
