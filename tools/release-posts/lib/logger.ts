/**
 * External dependencies
 */
import ora, { Ora } from 'ora';
import chalk from 'chalk';

/**
 * Internal dependencies
 */
import { getEnvVar } from './environment';

const { log, error, warn } = console;
export class Logger {
	private static lastSpinner: Ora | null;
	private static get loggingLevel() {
		return {
			warn: 2,
			silent: 1,
		}[ getEnvVar( 'LOGGER_LEVEL' ) || 'warn' ] as number;
	}

	static error( message: string ) {
		Logger.failTask();
		error( chalk.red( message ) );
		process.exit( 1 );
	}

	static warn( message: string ) {
		if ( Logger.loggingLevel >= 2 ) {
			warn( chalk.yellow( message ) );
		}
	}

	static notice( message: string ) {
		if ( Logger.loggingLevel >= 1 ) {
			log( chalk.green( message ) );
		}
	}

	static startTask( message: string ) {
		if ( Logger.loggingLevel >= 1 ) {
			const spinner = ora( chalk.green( `${ message }...` ) ).start();
			Logger.lastSpinner = spinner;
		}
	}

	static failTask() {
		if ( Logger.lastSpinner ) {
			Logger.lastSpinner.fail( `${ Logger.lastSpinner.text } failed.` );
			Logger.lastSpinner = null;
		}
	}

	static endTask() {
		if ( Logger.loggingLevel > 1 && Logger.lastSpinner ) {
			Logger.lastSpinner.succeed(
				`${ Logger.lastSpinner.text } complete.`
			);
			Logger.lastSpinner = null;
		}
	}
}
