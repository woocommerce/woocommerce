/**
 * External dependencies
 */
import { promisify } from 'util';
import { exec } from 'child_process';

const execPromisified = promisify( exec );

/**
 * Runs a WP-CLI command inside the tests-cli container.
 */
export async function wpCLI( command: string ) {
	return await execPromisified(
		'npm run wp-env run tests-cli -- wp ' + command
	);
}
