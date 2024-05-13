/**
 * External dependencies
 */
import { exec, ExecException } from 'child_process';

interface ExecResult {
	stdout: string;
	stderr: string;
}

interface ExecError extends ExecResult {
	error: ExecException | null;
}

export function cli( command: string ): Promise< ExecResult > {
	return new Promise( ( resolve, reject ) => {
		exec(
			command,
			( error: ExecException | null, stdout: string, stderr: string ) => {
				if ( error ) {
					reject( {
						error,
						stdout,
						stderr,
					} as ExecError );
				} else {
					resolve( {
						stdout,
						stderr,
					} as ExecResult );
				}
			}
		);
	} );
}
