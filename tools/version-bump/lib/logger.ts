/**
 * External dependencies
 */
const chalk = require( 'chalk' );

const { error } = console;

/**
 * This is a temporary Logger until a common one is built.
 */
export class Logger {
	static error( message: string ) {
		error( chalk.red( message ) );
		process.exit( 1 );
	}
}
