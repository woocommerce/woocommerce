const { promisify } = require( 'util' );

const execAsync = promisify( require( 'child_process' ).exec );

export const wpCLI = async ( command ) => {
	const { stdout, stderr } = await execAsync(
		`pnpm exec wp-env run tests-cli -- ${ command }`
	);

	console.log( stdout );
	console.error( stderr );
};
