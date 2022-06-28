/**
 * External dependencies
 */
import { execSync } from 'child_process';
import { readdirSync } from 'fs';
import { join } from 'path';

/**
 * Internal dependencies
 */
import { getFilepathFromPackageName } from './validate';

/**
 * Call changelogger's next version function to get the version for the next release.
 *
 * @param {string} name Package name.
 * @return {string} Next release version.
 */
export const getNextVersion = ( name: string ) => {
	try {
		const cwd = getFilepathFromPackageName( name );
		return execSync( './vendor/bin/changelogger version next', {
			cwd,
			encoding: 'utf-8',
		} ).trim();
	} catch ( e ) {
		let message = '';
		if ( e instanceof Error ) {
			message = e.message;
			throw new Error( message );
		}
	}
};

/**
 * Call Changelogger's validate function on changelog entries.
 *
 * @param {string} name
 * @return {Error|void} Output of changelogger exec.
 */
export const validateChangelogEntries = ( name: string ) => {
	try {
		const cwd = getFilepathFromPackageName( name );
		return execSync( './vendor/bin/changelogger validate', {
			cwd,
			encoding: 'utf-8',
		} );
	} catch ( e ) {
		let message = '';
		if ( e instanceof Error ) {
			message = e.message;
			throw new Error( message );
		}
	}
};

/**
 * Write the changelog.
 *
 * @param {string} name Package name.
 */
export const writeChangelog = ( name: string ) => {
	try {
		const cwd = getFilepathFromPackageName( name );
		execSync( './vendor/bin/changelogger write', {
			cwd,
			encoding: 'utf-8',
		} );
	} catch ( e ) {
		let message = '';
		if ( e instanceof Error ) {
			message = e.message;
			throw new Error(
				message + ' - Package may not have changelog entries.'
			);
		}
	}
};

/**
 * Determine if a package has changelogs to release.
 *
 * @param {string} name Package name.
 * @return {boolean} If there are changelogs.
 */
export const hasChangelogs = ( name: string ): boolean | void => {
	try {
		const changelogDir = join(
			getFilepathFromPackageName( name ),
			'changelog'
		);
		const changelogDirContents = readdirSync( changelogDir, {
			encoding: 'utf-8',
		} );

		return (
			changelogDirContents.filter( ( entry ) => entry !== '.gitkeep' )
				.length > 0
		);
	} catch ( e ) {
		let message = '';
		if ( e instanceof Error ) {
			message = e.message;
			throw new Error( message );
		}
	}
};
