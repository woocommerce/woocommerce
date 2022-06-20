/**
 * External dependencies
 */
import { execSync } from 'child_process';

/**
 * Internal dependencies
 */
import { getFilepathFromPackageName } from './validate';

export const getNextVersion = ( name: string ) => {
	const cwd = getFilepathFromPackageName( name );
	return execSync( './vendor/bin/changelogger version next', {
		cwd,
		encoding: 'utf-8',
	} );
};

export const validateChangelogEntries = ( name: string ) => {
	const cwd = getFilepathFromPackageName( name );
	return execSync( './vendor/bin/changelogger validate', {
		cwd,
		encoding: 'utf-8',
	} );
};

export const writeChangelog = ( name: string ) => {
	const cwd = getFilepathFromPackageName( name );
	execSync( './vendor/bin/changelogger write', {
		cwd,
		encoding: 'utf-8',
	} );
};
