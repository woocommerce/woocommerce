/**
 * External dependencies
 */
import { execSync } from 'child_process';

/**
 * Internal dependencies
 */
import { getFilepathFromPackageName } from './validate';

export const getNextVersion = ( name: string ) => {
	try {
		const cwd = getFilepathFromPackageName( name );
		return execSync( './vendor/bin/changelogger version next', {
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
