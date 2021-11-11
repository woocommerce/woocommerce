/**
 * External dependencies
 */
import { ExecutorContext } from '@nrwl/devkit';
import { spawn } from 'child_process';
import { join } from 'path';
import { existsSync } from 'fs';
import * as chalk from 'chalk';

export interface ChangelogExecutorOptions {
	action: string;
	cwd: string;
}

const changeloggerScriptPath = 'vendor/bin/changelogger';

async function runChangelogger( {
	action,
}: ChangelogExecutorOptions ): Promise< { code: number; error: string } > {
	return new Promise( ( resolve, reject ) => {
		const changeloggerScript = spawn(
			`./${ changeloggerScriptPath }`,
			[ action ],
			{
				stdio: 'inherit',
			}
		);

		changeloggerScript.on( 'close', ( code ) => {
			resolve( { code, error: undefined } );
		} );

		changeloggerScript.on( 'error', ( error ) => {
			reject( { code: 1, error } );
		} );
	} );
}

export default async function changelogExecutor(
	options: ChangelogExecutorOptions,
	context: ExecutorContext
) {
	const { cwd } = options;
	const projectPath = join( __dirname, '../../../', cwd );

	console.info( chalk.cyan( `\nExecuting Changelogger...\n` ) );

	try {
		process.chdir( projectPath );
		console.log(
			chalk.yellow( 'Executing from directory: ' + process.cwd() + '\n' )
		);
	} catch ( error ) {
		console.error(
			chalk.bgRed( 'Unable to find project working directory' )
		);
		console.error( error );
		return { success: false };
	}

	if ( ! existsSync( changeloggerScriptPath ) ) {
		console.error(
			chalk.bgRed(
				'Changelogger scripts not found. Did you remember to `composer install` from project directory?'
			)
		);
		return { success: false };
	}

	const { code, error } = await runChangelogger( options );

	if ( error ) {
		console.error( chalk.bgRed( error ) );
		return { success: false };
	}

	return { success: code === 0 };
}
