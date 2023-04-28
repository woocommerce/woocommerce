/**
 * External dependencies
 */
import { join } from 'path';
import { readFile, writeFile } from 'fs/promises';

export const bumpFiles = async ( tmpRepoPath ) => {
	const filePath = join( tmpRepoPath, `plugins/woocommerce/woocommerce.php` );
};
