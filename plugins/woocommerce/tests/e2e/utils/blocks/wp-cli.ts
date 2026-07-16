/**
 * External dependencies
 */
import { promisify } from 'util';
import { exec, execFile } from 'child_process';

const execPromisified = promisify( exec );
const execFilePromisified = promisify( execFile );

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
 * Resets the Blocks E2E database and imports its snapshot in one CLI-container
 * invocation.
 */
export async function restoreBlocksDatabase( databaseFile: string ) {
	return await execFilePromisified( 'npm', [
		'run',
		'wp-env:e2e',
		'run',
		'cli',
		'--',
		'sh',
		'-c',
		'wp db reset --yes && wp db import "$1"',
		'restore-blocks-database',
		databaseFile,
	] );
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
