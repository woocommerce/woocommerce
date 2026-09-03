/**
 * External dependencies
 */
import { promisify } from 'util';
import { exec, execFile } from 'child_process';

const execAsync = promisify( exec );
const execFileAsync = promisify( execFile );

/**
 * Runs a command in the E2E CLI container. Use an argument array when the command contains dynamic values.
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
