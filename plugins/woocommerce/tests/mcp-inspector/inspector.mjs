import { spawn } from 'node:child_process';
import { existsSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

export const PLUGIN_DIR = path.resolve(
	path.dirname( fileURLToPath( import.meta.url ) ),
	'../..'
);

const INSPECTOR_BIN =
	process.env.MCP_INSPECTOR_BIN ??
	path.join( PLUGIN_DIR, 'node_modules/.bin/mcp-inspector' );

// The wp-env environment is found from the working directory, so this can point at a checkout whose env is already running.
const WP_ENV_CWD = process.env.WP_ENV_CWD ?? PLUGIN_DIR;

const TIMEOUT_MS = 120_000;

function toolArg( key, value ) {
	const encoded =
		typeof value === 'string' ? value : JSON.stringify( value );
	return [ '--tool-arg', `${ key }=${ encoded }` ];
}

/**
 * Runs one MCP request through the Inspector CLI against `wp mcp-adapter serve`, acting as `user`.
 *
 * Resolves with the parsed response even when the tool reports an error, which the CLI signals with a non-zero exit code.
 */
export function inspect( { user, method, toolName, toolArgs = {} } ) {
	if ( ! existsSync( INSPECTOR_BIN ) ) {
		throw new Error(
			`MCP Inspector is not installed at ${ INSPECTOR_BIN }. Install @modelcontextprotocol/inspector@2.11.0 and point MCP_INSPECTOR_BIN at its mcp-inspector binary.`
		);
	}

	const args = [
		'--cli',
		'--transport',
		'stdio',
		'--cwd',
		WP_ENV_CWD,
		'--method',
		method,
	];

	if ( toolName ) {
		args.push( '--tool-name', toolName );
		for ( const [ key, value ] of Object.entries( toolArgs ) ) {
			args.push( ...toolArg( key, value ) );
		}
	}

	// Without `--`, the Inspector takes the adapter's `--server=` as its own flag.
	args.push(
		'--',
		'pnpm',
		'exec',
		'wp-env',
		'run',
		'cli',
		'wp',
		'mcp-adapter',
		'serve',
		'--server=mcp-adapter-default-server',
		`--user=${ user }`
	);

	const attempt = ( retries ) =>
		run( args ).catch( ( error ) => {
			if ( retries > 0 && WP_ENV_RACE.test( error.message ) ) {
				return attempt( retries - 1 );
			}
			throw error;
		} );

	return attempt( 2 );
}

// Concurrent `wp-env run` calls occasionally read the environment's state while another call rewrites it.
const WP_ENV_RACE = /Environment not initialized/;

function run( args ) {
	return new Promise( ( resolve, reject ) => {
		const child = spawn( INSPECTOR_BIN, args, { timeout: TIMEOUT_MS } );
		let stdout = '';
		let stderr = '';
		child.stdout.on( 'data', ( chunk ) => ( stdout += chunk ) );
		child.stderr.on( 'data', ( chunk ) => ( stderr += chunk ) );
		child.on( 'error', reject );
		child.on( 'close', ( code ) => {
			let response;
			try {
				response = JSON.parse( stdout );
			} catch {}

			if ( response && ! response.error ) {
				resolve( response );
				return;
			}

			reject(
				new Error(
					`Inspector exited with ${ code } and no MCP response.\n${ stderr }${ stdout }`
				)
			);
		} );
	} );
}

export function callTool( user, toolName, toolArgs ) {
	return inspect( { user, method: 'tools/call', toolName, toolArgs } );
}

/**
 * The meta tools return their payload as JSON text; structured content is only set on success.
 */
export function payloadOf( response ) {
	if ( response.structuredContent ) {
		return response.structuredContent;
	}
	const text = response.content?.find( ( item ) => item.type === 'text' )
		?.text;
	try {
		return JSON.parse( text );
	} catch {
		return text;
	}
}

export async function mapWithLimit( items, limit, fn ) {
	const results = new Array( items.length );
	let next = 0;
	const worker = async () => {
		while ( next < items.length ) {
			const index = next++;
			results[ index ] = await fn( items[ index ] );
		}
	};
	await Promise.all(
		Array.from( { length: Math.min( limit, items.length ) }, worker )
	);
	return results;
}
