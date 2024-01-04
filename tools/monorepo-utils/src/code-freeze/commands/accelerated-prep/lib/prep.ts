/**
 * External dependencies
 */
import { readFile, writeFile } from 'fs/promises';
import { join } from 'path';

/**
 * Internal dependencies
 */
import { Logger } from '../../../../core/logger';

/**
 * Add Woo header to main plugin file.
 *
 * @param tmpRepoPath cloned repo path
 */
export const addHeader = async ( tmpRepoPath: string ): Promise< void > => {
	const filePath = join( tmpRepoPath, 'plugins/woocommerce/woocommerce.php' );
	try {
		const pluginFileContents = await readFile( filePath, 'utf8' );

		const updatedPluginFileContents = pluginFileContents.replace(
			' * @package WooCommerce\n */',
			' *\n * Woo: 18734002369816:624a1b9ba2fe66bb06d84bcdd401c6a6\n *\n * @package WooCommerce\n */'
		);

		await writeFile( filePath, updatedPluginFileContents );
	} catch ( e ) {
		Logger.error( e );
	}
};

/**
 * Create changelog file.
 *
 * @param tmpRepoPath cloned repo path
 * @param version     version for the changelog file
 * @param date        date of the release (Y-m-d)
 */
export const createChangelog = async (
	tmpRepoPath: string,
	version: string,
	date: string
): Promise< void > => {
	const filePath = join( tmpRepoPath, 'plugins/woocommerce/changelog.txt' );
	try {
		const changelogContents = `*** WooCommerce ***

${ date } - Version ${ version }
* Update - Deploy of WooCommerce ${ version }
`;

		await writeFile( filePath, changelogContents );
	} catch ( e ) {
		Logger.error( e );
	}
};
