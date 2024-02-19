/**
 * External dependencies
 */
import { ExecException, exec } from 'child_process';

type CliOutput = {
	code: number;
	error: ExecException | null;
	stdout: string;
	stderr: string;
};

export function cli( cmd: string, args = [] ) {
	return new Promise< CliOutput >( ( resolve ) => {
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
