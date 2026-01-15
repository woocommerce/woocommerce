const { promisify } = require( 'util' );

const execAsync = promisify( require( 'child_process' ).exec );

const wpCLI = async ( command ) => {
	const { stdout, stderr } = await execAsync(
		`pnpm exec wp-env run tests-cli -- ${ command }`
	);

	return { stdout, stderr };
};

module.exports = {
	wpCLI,
};
