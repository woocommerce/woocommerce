/**
 * External dependencies
 */
import { promisify } from 'util';
import { exec } from 'child_process';

const execAsync = promisify( exec );

const getInstalledWordPressVersion = async () => {
	try {
		const { stdout } = await execAsync(
			`pnpm exec wp-env --config .wp-env.e2e.json run cli -- wp core version`
		);

		return Number.parseFloat( stdout.trim() );
	} catch ( error ) {
		throw new Error(
			`Error getting WordPress version: ${ error.message }`
		);
	}
};

export { getInstalledWordPressVersion };
