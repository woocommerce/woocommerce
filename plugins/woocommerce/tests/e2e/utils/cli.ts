/**
 * External dependencies
 */
import { promisify } from 'util';
import { exec } from 'child_process';

const execAsync = promisify( exec );

const wpCLI = async ( command: string ) => {
	const { stdout, stderr } = await execAsync(
		`pnpm exec wp-env --config .wp-env.e2e.json run cli -- ${ command }`
	);

	return { stdout, stderr };
};

export { wpCLI };
