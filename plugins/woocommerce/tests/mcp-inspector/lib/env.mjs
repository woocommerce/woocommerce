import { spawn } from 'node:child_process';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

export const PLUGIN_DIR = path.resolve(
	path.dirname( fileURLToPath( import.meta.url ) ),
	'../../..'
);

// The wp-env environment is found from the working directory, so this can point at a checkout whose env is already running.
const WP_ENV_CWD = process.env.WP_ENV_CWD ?? PLUGIN_DIR;

const TIMEOUT_MS = 120_000;

/**
 * Resolves with the exit code and output whatever the exit code, so callers decide what counts as a failure.
 */
export function run( [ command, ...args ], { cwd = WP_ENV_CWD } = {} ) {
	return new Promise( ( resolve, reject ) => {
		const child = spawn( command, args, { cwd, timeout: TIMEOUT_MS } );
		let stdout = '';
		let stderr = '';
		child.stdout.on( 'data', ( chunk ) => ( stdout += chunk ) );
		child.stderr.on( 'data', ( chunk ) => ( stderr += chunk ) );
		child.on( 'error', reject );
		child.on( 'close', ( code ) => resolve( { code, stdout, stderr } ) );
	} );
}

/**
 * Every wp-env command rewrites the environment's cache file without a lock, and a parallel command reading it
 * half-written fails with "Environment not initialized" or clears the cache. So wp-env runs once, here, and the
 * tests reach the container through Docker Compose, as `wp-env run` itself does.
 */
export async function resolveComposeFile() {
	const { stdout } = await run( [
		'pnpm',
		'exec',
		'wp-env',
		'--config',
		'.wp-env.mcp.json',
		'status',
		'--json',
	] );
	let status;
	try {
		status = JSON.parse( stdout.slice( stdout.indexOf( '{' ) ) );
	} catch {}

	if ( status?.status !== 'running' ) {
		throw new Error(
			`The MCP environment is not running in ${ WP_ENV_CWD }. Start it with \`pnpm env:mcp\`.\n${ stdout }`
		);
	}
	return path.join( status.installPath, 'docker-compose.yml' );
}

let composeFile;

/**
 * The command that runs WP-CLI in the environment's `cli` container, as the host user like `wp-env run`.
 */
export async function wpCli( ...args ) {
	composeFile ??= process.env.MCP_WP_ENV_COMPOSE_FILE ?? resolveComposeFile();
	const { uid, gid } = os.userInfo();

	return [
		'docker',
		'compose',
		'-f',
		await composeFile,
		'exec',
		'-T',
		'-w',
		'/var/www/html',
		'--user',
		`${ uid }:${ gid }`,
		'cli',
		'wp',
		...args,
	];
}
