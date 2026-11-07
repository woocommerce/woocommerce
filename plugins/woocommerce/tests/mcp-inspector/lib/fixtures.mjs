import { run, wpCli } from './env.mjs';

const RUNNER =
	'wp-content/plugins/woocommerce/tests/mcp-inspector/fixtures/run.php';

const MARKER = 'MCP_FIXTURE=';

/**
 * Runs `fixtures/<name>.php` in the environment with `args`, and resolves with what it returns.
 */
export async function seed( name, args = {} ) {
	const { code, stdout, stderr } = await run(
		await wpCli( 'eval-file', RUNNER, name, JSON.stringify( args ) )
	);
	const line = stdout
		.split( '\n' )
		.find( ( candidate ) => candidate.startsWith( MARKER ) );

	if ( code !== 0 || ! line ) {
		throw new Error(
			`Fixture ${ name } failed with exit code ${ code }.\n${ stderr }${ stdout }`
		);
	}
	return JSON.parse( line.slice( MARKER.length ) );
}
