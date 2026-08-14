/**
 * External dependencies
 */
import { promisify } from 'util';
import { exec } from 'child_process';

const execAsync = promisify( exec );

const wpCLI = async ( command: string ) => {
	// Target the running instance: the base `.wp-env.e2e.json` by default, or the
	// variant named by E2E_WP_ENV_CONFIG when a plugin-installing environment
	// (Gutenberg, object cache) is running.
	const wpEnvConfig = process.env.E2E_WP_ENV_CONFIG || '.wp-env.e2e.json';
	const { stdout, stderr } = await execAsync(
		`pnpm exec wp-env --config ${ wpEnvConfig } run cli -- ${ command }`
	);

	return { stdout, stderr };
};

export { wpCLI };
