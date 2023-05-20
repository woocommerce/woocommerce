/**
 * External dependencies
 */
import { readFile, writeFile } from 'fs/promises';
import { existsSync } from 'fs';
import { join } from 'path';

/**
 * Internal dependencies
 */
import { Logger } from '../../../../core/logger';

export const updateFileLine = async (
	tmpRepoPath: string,
	filePath: string,
	line: number,
	content: string
) => {
	try {
		const fullFilePath = join( tmpRepoPath, filePath );
		const fileContents = await readFile( fullFilePath, 'utf8' );
		const lines = fileContents.split( '\n' );
		lines[ line - 1 ] = content;

		await writeFile( fullFilePath, lines.join( '\n' ) );
	} catch ( e ) {
		Logger.error( e );
	}
};
