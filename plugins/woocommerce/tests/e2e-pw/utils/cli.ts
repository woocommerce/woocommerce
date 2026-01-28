/**
 * External dependencies
 */
import { promisify } from 'util';
import { exec } from 'child_process';

const execAsync = promisify( exec );

/**
 * Result from WP-CLI command execution.
 */
export interface WpCliResult {
	stdout: string;
	stderr: string;
}

/**
 * Execute a WP-CLI command in the test environment.
 *
 * @param command - The WP-CLI command to execute
 * @return Promise resolving to stdout and stderr
 */
export const wpCLI = async ( command: string ): Promise< WpCliResult > => {
	const { stdout, stderr } = await execAsync(
		`pnpm exec wp-env run tests-cli -- ${ command }`
	);

	return { stdout, stderr };
};
