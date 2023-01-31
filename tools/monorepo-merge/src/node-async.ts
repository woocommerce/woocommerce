/**
 * External dependencies
 */
import { access as nodeAccess, PathLike } from 'fs';
import { exec as nodeExec, ExecOptions } from 'child_process';

/**
 * A promise wrapper for Node's `fs.access` function.
 *
 * @param {string} path   The path to access.
 * @param {number} [mode] The access mode.
 */
export async function access( path: PathLike, mode?: number ): Promise< void > {
	return new Promise( ( resolve, reject ) => {
		nodeAccess( path, mode, ( err ) => {
			if ( err ) {
				reject( err );
				return;
			}

			resolve();
		} );
	} );
}

/**
 * A promise wrapper for Node's `child_process.exec` function.
 *
 * @param {string}      command   The command to execute.
 * @param {ExecOptions} [options] The options for the command.
 */
export async function exec(
	command: string,
	options?: ExecOptions
): Promise< string | Buffer > {
	return new Promise( ( resolve, reject ) => {
		nodeExec( command, options, ( err, stdout, stderr ) => {
			if ( err ) {
				reject( stderr );
				return;
			}

			resolve( stdout );
		} );
	} );
}
