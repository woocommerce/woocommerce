/**
 * External dependencies
 */
import { execSync } from 'child_process';
import { readdirSync, readFileSync } from 'fs';
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
		if ( e instanceof Error ) {
			// eslint-disable-next-line no-console
			console.log( e );
			throw e;
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
		if ( e instanceof Error ) {
			// eslint-disable-next-line no-console
			console.log( e );
			throw e;
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
		execSync( './vendor/bin/changelogger write --add-pr-num', {
			cwd,
			encoding: 'utf-8',
		} );
	} catch ( e ) {
		if ( e instanceof Error ) {
			// eslint-disable-next-line no-console
			console.log( e );
			throw e;
		}
	}
};

/**
 * Determine if a package has changelogs to release.
 *
 * @param {string} name Package name.
 * @return {boolean} If there are changelogs.
 */
export const hasValidChangelogs = ( name: string ): boolean | void => {
	try {
		const changelogDir = join(
			getFilepathFromPackageName( name ),
			'changelog'
		);
		const changelogDirContents = readdirSync( changelogDir, {
			encoding: 'utf-8',
		} );

		const changelogs = changelogDirContents.filter(
			( entry ) => entry !== '.gitkeep'
		);

		if ( changelogs.length === 0 ) {
			return false;
		}

		// If there is at least one changelog that is not just a comment, there are valid changelogs.
		return changelogs.some( ( changelog ) => {
			const contents = readFileSync(
				join( changelogDir, changelog ),
				'utf8'
			);

			const commentRegex = /Comment:.*\n([\s\S]*)?/;
			const hasComment = commentRegex.test( contents );

			if ( ! hasComment ) {
				// Has no comment, must be a real changelog.
				return true;
			}

			const textAfterComment = /Comment:.*\n([\s\S]*)?/.exec( contents );

			if ( textAfterComment ) {
				// Return true if there is more than just whitespace.
				return textAfterComment[ 1 ].trim().length > 0;
			}

			return false;
		} );
	} catch ( e ) {
		if ( e instanceof Error ) {
			// eslint-disable-next-line no-console
			console.log( e );
			throw e;
		}
	}
};
