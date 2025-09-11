const { promisify } = require( 'util' );

const execAsync = promisify( require( 'child_process' ).exec );

const wpCLI = async ( command ) => {
	// Check if we're in QIT environment
	if (process.env.QIT_ENV_ID) {
		const { stdout, stderr } = await execAsync(
			`qit env:exec --env_id=${ process.env.QIT_ENV_ID } "${ command } --allow-root"`
		);
		return { stdout, stderr };
	}
	
	// Fallback to wp-env for local development
	const { stdout, stderr } = await execAsync(
		`pnpm exec wp-env run tests-cli -- ${ command }`
	);

	return { stdout, stderr };
};

module.exports = {
	wpCLI,
};
