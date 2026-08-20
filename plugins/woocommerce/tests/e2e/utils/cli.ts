/**
 * External dependencies
 */
import { promisify } from 'util';
import { exec } from 'child_process';

const execAsync = promisify( exec );

const wpCLI = async ( command: string ) => {
	// E2E_WP_ENV_CONFIG names the running wp-env instance; run-tests-with-env.sh
	// sets it from the environment name (base config or a plugin-installing
	// variant such as Gutenberg or object cache).
	const { stdout, stderr } = await execAsync(
		`pnpm exec wp-env --config ${ process.env.E2E_WP_ENV_CONFIG } run cli -- ${ command }`
	);

	return { stdout, stderr };
};

export { wpCLI };
