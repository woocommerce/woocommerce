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
			error: 3,
			warn: 2,
			silent: 1,
		}[ getEnvVar( 'LOGGER_LEVEL' ) || 'warn' ] as number;
	}

	static error( message: string ) {
		if ( Logger.loggingLevel >= 3 ) {
			// eslint-disable-next-line no-console
			error( chalk.red( message ) );
			process.exit( 1 );
		}
	}

	static warn( message: string ) {
		if ( Logger.loggingLevel >= 2 ) {
			// eslint-disable-next-line no-console
			warn( chalk.yellow( message ) );
		}
	}

	static notice( message: string ) {
		if ( Logger.loggingLevel >= 1 ) {
			// eslint-disable-next-line no-console
			log( chalk.green( message ) );
		}
	}

	static startTask( message: string ) {
		if ( Logger.loggingLevel >= 1 ) {
			const spinner = ora( chalk.green( `${ message }...` ) ).start();
			Logger.lastSpinner = spinner;
		}
	}

	static endTask() {
		if ( Logger.loggingLevel >= 1 && Logger.lastSpinner ) {
			Logger.lastSpinner.succeed(
				`${ Logger.lastSpinner.text } complete.`
			);
			Logger.lastSpinner = null;
		}
	}
}
