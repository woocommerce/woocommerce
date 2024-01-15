/**
 * External dependencies
 */
import fs from 'node:fs';
import path from 'node:path';

export interface PackageJSON {
	name: string;
	config?: { ci?: any };
	dependencies?: { [ key: string ]: string };
	devDependencies?: { [ key: string ]: string };
}

// We're going to store a cache of package files so that we don't load
// ones that we have already loaded. The key is the normalized path
// to the package file that was loaded.
const packageCache: { [ key: string ]: PackageJSON } = {};

/**
 * Loads a package file's contents either from the cache or from the file system.
 *
 * @param {string} packagePath The package file to load.
 * @return {Object} The package file's contents.
 */
export function loadPackage( packagePath: string ): PackageJSON {
	// Use normalized paths to accomodate any path tokens.
	packagePath = path.normalize( packagePath );
	if ( packageCache[ packagePath ] ) {
		return packageCache[ packagePath ];
	}

	packageCache[ packagePath ] = JSON.parse(
		fs.readFileSync( packagePath, 'utf8' )
	);
	return packageCache[ packagePath ];
}
