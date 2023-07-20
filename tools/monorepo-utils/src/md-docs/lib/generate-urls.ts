/**
 * External dependencies
 */
import path from 'path';

/**
 * Generates a file url relative to the root directory provided.
 *
 * @param baseUrl          The base url to use for the file url.
 * @param rootDirectory    The root directory where the file resides.
 * @param subDirectory     The sub-directory where the file resides.
 * @param absoluteFilePath The absolute path to the file.
 * @return The file url.
 */
export const generateFileUrl = (
	baseUrl: string,
	rootDirectory: string,
	subDirectory: string,
	absoluteFilePath: string
) => {
	// check paths are absolute
	for ( const filePath of [
		rootDirectory,
		subDirectory,
		absoluteFilePath,
	] ) {
		if ( ! path.isAbsolute( filePath ) ) {
			throw new Error(
				`File URLs cannot be generated without absolute paths. ${ filePath } is not absolute.`
			);
		}
	}
	// Generate a path from the subdirectory to the file path.
	const relativeFilePath = path.resolve( subDirectory, absoluteFilePath );

	// Determine the relative path from the rootDirectory to the filePath.
	const relativePath = path.relative( rootDirectory, relativeFilePath );

	return `${ baseUrl }/${ relativePath }`;
};
