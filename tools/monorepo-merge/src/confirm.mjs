/**
 * External dependencies
 */
import { stdin as input, stdout as output } from 'node:process';
import { createInterface } from 'node:readline/promises';

/**
 * Asks a yes/no question on the terminal. Anything other than "y" or "yes" is a no.
 *
 * @param {string} message The question to ask.
 * @return {Promise<boolean>} Whether the user answered yes.
 */
export async function confirm( message ) {
	const readline = createInterface( { input, output } );

	try {
		const answer = await readline.question( `${ message } (y/N) ` );

		return /^y(es)?$/i.test( answer.trim() );
	} finally {
		readline.close();
	}
}
