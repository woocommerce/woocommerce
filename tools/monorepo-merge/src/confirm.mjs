/**
 * External dependencies
 */
import { stdin as input, stdout as output } from 'node:process';
import { createInterface } from 'node:readline';

// One reader for every prompt, so answers piped in ahead of time are not dropped between questions.
let readline;
let lines;

/**
 * Asks a yes/no question on the terminal. Anything other than "y" or "yes" is a no,
 * as are end of input and Ctrl+C.
 *
 * @param {string} message The question to ask.
 * @return {Promise<boolean>} Whether the user answered yes.
 */
export async function confirm( message ) {
	if ( ! readline ) {
		readline = createInterface( { input, output } );
		readline.on( 'SIGINT', () => readline.close() );
		lines = readline[ Symbol.asyncIterator ]();
	}

	output.write( `${ message } (y/N) ` );
	const { value, done } = await lines.next();

	return ! done && /^y(es)?$/i.test( value.trim() );
}

/**
 * Releases the terminal so the process can exit.
 */
export function closePrompts() {
	readline?.close();
}
