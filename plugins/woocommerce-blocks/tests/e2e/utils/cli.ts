/**
 * External dependencies
 */
import { ExecException, exec } from 'child_process';

export function cli(
	cmd: string,
	args = []
): Promise< {
	code: number;
	error: ExecException | null;
	stdout: string;
	stderr: string;
} > {
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
