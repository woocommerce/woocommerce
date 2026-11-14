import { existsSync } from 'node:fs';
import path from 'node:path';
import { PLUGIN_DIR, run, wpCli } from './env.mjs';

const INSPECTOR_BIN =
	process.env.MCP_INSPECTOR_BIN ??
	path.join( PLUGIN_DIR, 'node_modules/.bin/mcp-inspector' );

function toolArg( key, value ) {
	const encoded = typeof value === 'string' ? value : JSON.stringify( value );
	return [ '--tool-arg', `${ key }=${ encoded }` ];
}

/**
 * Runs one MCP request through the Inspector CLI against `wp mcp-adapter serve`, acting as `user`.
 *
 * Resolves with the parsed response even when the tool reports an error, which the CLI signals with a non-zero exit code.
 */
export async function inspect( { user, method, toolName, toolArgs = {} } ) {
	if ( ! existsSync( INSPECTOR_BIN ) ) {
		throw new Error(
			`MCP Inspector is not installed at ${ INSPECTOR_BIN }. Run \`pnpm install\` first.`
		);
	}

	const args = [ '--cli', '--transport', 'stdio', '--method', method ];

	if ( toolName ) {
		args.push( '--tool-name', toolName );
		for ( const [ key, value ] of Object.entries( toolArgs ) ) {
			args.push( ...toolArg( key, value ) );
		}
	}

	// Without `--`, the Inspector takes the adapter's `--server=` as its own flag.
	args.push(
		'--',
		...( await wpCli(
			'mcp-adapter',
			'serve',
			'--server=mcp-adapter-default-server',
			`--user=${ user }`
		) )
	);

	const { code, stdout, stderr } = await run( [ INSPECTOR_BIN, ...args ] );
	let response;
	try {
		response = JSON.parse( stdout );
	} catch {}

	if ( ! response || response.error ) {
		throw new Error(
			`Inspector exited with ${ code } and no MCP response.\n${ stderr }${ stdout }`
		);
	}
	return response;
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
	const text = response.content?.find(
		( item ) => item.type === 'text'
	)?.text;
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
