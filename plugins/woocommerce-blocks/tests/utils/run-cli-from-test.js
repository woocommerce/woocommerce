/**
 * External dependencies
 */
import { exec } from 'child_process';

export function cli( cmd, args = [] ) {
	return new Promise( ( resolve ) => {
		exec( `${ cmd } ${ args.join( ' ' ) }`, ( error, stdout, stderr ) => {
			resolve( {
				code: error && error.code ? error.code : 0,
				error,
				stdout,
				stderr,
			} );
		} );
	} );
}
