/**
 * External dependencies
 */
import { promisify } from 'util';
import { exec } from 'child_process';

const execPromisified = promisify( exec );

/**
 * Runs a command inside the tests-cli container.
 */
export async function testsCli( command: string ) {
	return await execPromisified(
		'npm run wp-env run tests-cli -- ' + command
	);
}
