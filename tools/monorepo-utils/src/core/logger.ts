/**
 * External dependencies
 */
import ora, { Ora } from 'ora';
import chalk from 'chalk';
import Table from 'cli-table';

/**
 * Internal dependencies
 */
import { getEnvVar, isGithubCI } from './environment';

const LOGGING_LEVELS: Record< string, number > = {
	verbose: 3,
	warn: 2,
	error: 1,
	silent: 0,
};

const { log, error, warn } = console;
export class Logger {
	private static lastSpinner: Ora | null;
	private static get loggingLevel() {
		return LOGGING_LEVELS[
			getEnvVar( 'LOGGER_LEVEL' ) || 'warn'
		] as number;
	}

	static error( err: unknown, failOnErr = true ) {
		if ( Logger.loggingLevel >= LOGGING_LEVELS.error ) {
			if ( err instanceof Error ) {
				error( chalk.red( `${ err.message }\n${ err.stack }` ) );
			} else if ( typeof err === 'string' ) {
				error( chalk.red( err ) );
			} else {
				// Best effort to log the error when we don't know the type.
				error( chalk.red( JSON.stringify( err, null, 2 ) ) );
			}

			if ( failOnErr ) {
				process.exit( 1 );
			}
		}
	}

	static warn( message: string ) {
		if ( Logger.loggingLevel >= LOGGING_LEVELS.warn ) {
			warn( chalk.yellow( message ) );
		}
	}

	static notice( message: string ) {
		if ( Logger.loggingLevel > LOGGING_LEVELS.silent ) {
			log( chalk.green( message ) );
		}
	}

	static startTask( message: string, isSilentInCI = false ) {
		if ( Logger.loggingLevel > LOGGING_LEVELS.silent && ! isGithubCI() ) {
			const spinner = ora( chalk.green( `${ message }...` ) ).start();
			Logger.lastSpinner = spinner;
		} else if ( isGithubCI() && ! isSilentInCI ) {
			Logger.notice( message );
		}
	}

	static table( head: Array< string >, rows: Array< string[] > ) {
		if ( Logger.loggingLevel > LOGGING_LEVELS.silent ) {
			const table = new Table( { head, rows } );
			log( table.toString() );
		}
	}

	static endTask( isSilentInCI = false ) {
		if (
			Logger.loggingLevel > LOGGING_LEVELS.silent &&
			Logger.lastSpinner &&
			! isGithubCI()
		) {
			Logger.lastSpinner.succeed(
				`${ Logger.lastSpinner.text } complete.`
			);
			Logger.lastSpinner = null;
		} else if ( isGithubCI() && ! isSilentInCI ) {
			Logger.notice( 'Task complete.' );
		}
	}
}
