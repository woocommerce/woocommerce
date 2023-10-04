/**
 * External dependencies
 */
import {
	statSync,
	existsSync,
	appendFileSync,
	writeFileSync,
	readFileSync,
} from 'fs';
import CLIError from '@wordpress/create-block/lib/cli-error';

/**
 * Internal dependencies
 */
import { error, info, success } from './log';

export function isGitDirectory(): boolean {
	try {
		statSync( '.git' );
		return true;
	} catch ( e ) {
		if ( ( e as { code: string } ).code === 'ENOENT' ) {
			return false;
		}
		throw new CLIError( 'Failed to read .git directory.' );
	}
}

function hasGitIgnore(): boolean {
	try {
		if ( existsSync( '.gitignore' ) ) {
			return true;
		}
		return false;
	} catch ( e ) {
		return false;
	}
}

export function updateOrCreateGitIgnore(
	prefixComment: string,
	ignores: string[]
): boolean {
	if ( hasGitIgnore() ) {
		try {
			const data = readFileSync( '.gitignore', 'utf8' );
			const unmatchedIgnores: string[] = [];
			for ( const ignore of ignores ) {
				if ( ! data.includes( ignore ) ) {
					unmatchedIgnores.push( ignore );
				}
			}
			const content =
				`\n# ${ prefixComment }\n` + unmatchedIgnores.join( '\n' );
			if ( unmatchedIgnores.length > 0 ) {
				info( 'Updating .gitignore with ' + prefixComment );
				appendFileSync( '.gitignore', content );
				success( 'Successfully updated .gitignore.' );
			}
			return true;
		} catch ( e ) {
			error( 'Failed to update .gitignore.' );
			return false;
		}
	} else {
		try {
			info( 'Creating .gitignore with ' + prefixComment );
			const content = `# ${ prefixComment }\n` + ignores.join( '\n' );
			writeFileSync( '.gitignore', content );
			success( 'Successfully created .gitignore.' );
			return true;
		} catch ( e ) {
			error( 'Failed to create .gitignore.' );
			return false;
		}
	}
}
