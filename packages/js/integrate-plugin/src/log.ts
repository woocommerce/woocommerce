/* eslint-disable no-console */
/**
 * External dependencies
 */
import chalk from 'chalk';

const code = ( input: string ) => {
	console.log( chalk.cyan( input ) );
};

const error = ( input: string ) => {
	console.log( chalk.bold.red( input ) );
};

const info = ( input: string ) => {
	console.log( input );
};
const success = ( input: string ) => {
	console.log( chalk.bold.green( input ) );
};

export { code, error, info, success };
/* eslint-enable no-console */
