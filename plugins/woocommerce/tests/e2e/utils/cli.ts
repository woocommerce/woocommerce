/**
 * External dependencies
 */
import { promisify } from 'util';
import { exec, execFile } from 'child_process';

const execAsync = promisify( exec );
const execFileAsync = promisify( execFile );

/**
 * Runs a command in the E2E CLI container. Use an argument array when the command contains dynamic values.
 *
 * Await each call before starting the next, never through `Promise.all`: wp-env rewrites its cache file without locking, and two overlapping calls can drop its `runtime` key so every later wp-env command fails with "Environment not initialized".
 */
const wpCLI = async ( command: string | string[] ) => {
	const { stdout, stderr } = Array.isArray( command )
		? await execFileAsync( 'pnpm', [
				'exec',
				'wp-env',
				'--config',
				'.wp-env.e2e.json',
				'run',
				'cli',
				'--',
				...command,
		  ] )
		: await execAsync(
				`pnpm exec wp-env --config .wp-env.e2e.json run cli -- ${ command }`
		  );

	return { stdout, stderr };
};

export { wpCLI };
