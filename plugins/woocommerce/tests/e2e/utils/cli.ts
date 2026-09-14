/**
 * External dependencies
 */
import { promisify } from 'util';
import { exec, execFile } from 'child_process';

const execAsync = promisify( exec );
const execFileAsync = promisify( execFile );

type CLIResult = { stdout: string; stderr: string };

/**
 * Queue tail. Each call chains onto the previous one, so only one wp-env process
 * runs at a time. A rejected call must not poison the queue, so the tail always
 * resolves.
 */
let queue: Promise< unknown > = Promise.resolve();

/**
 * Run a task once every task queued before it has settled.
 */
const enqueue = ( task: () => Promise< CLIResult > ): Promise< CLIResult > => {
	const result = queue.then( task, task );
	queue = result.catch( () => undefined );

	return result;
};

/**
 * Runs a command in the E2E CLI container. Use an argument array when the command contains dynamic values.
 *
 * Calls are queued rather than run in parallel. Every wp-env command rewrites
 * `wp-env-cache.json` in the environment's work directory, and it does so by reading the
 * file, adding a key and writing the whole thing back without any locking. Two commands
 * at once can interleave so that one reads the file while the other has truncated it,
 * sees an empty object, and writes back a cache that no longer holds the `runtime` key.
 * wp-env then refuses every later command, `run` and `destroy` alike, with "Environment
 * not initialized. Run `wp-env start` first." — for the rest of the job, since nothing
 * restores that key but `wp-env start`. Queueing costs a few seconds and removes the
 * whole failure mode.
 */
const wpCLI = async ( command: string | string[] ): Promise< CLIResult > =>
	enqueue( async () => {
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
	} );

export { wpCLI };
