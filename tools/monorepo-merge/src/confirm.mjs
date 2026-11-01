/**
 * External dependencies
 */
import { stdin as input, stdout as output } from 'node:process';
import { createInterface } from 'node:readline';

// One reader for every prompt, so answers piped in ahead of time are not dropped between questions.
// `terminal: false` leaves the terminal in its normal mode, so Ctrl+C still stops the tool and its git commands.
let readline;
let lines;
let closed = false;

/**
 * Asks a yes/no question on the terminal. Anything other than "y" or "yes" is a no, as is end of input.
 *
 * @param {string} message The question to ask.
 * @return {Promise<boolean>} Whether the user answered yes.
 */
export async function confirm( message ) {
	if ( ! readline ) {
		readline = createInterface( { input, terminal: false } );
		readline.on( 'close', () => ( closed = true ) );
		lines = readline[ Symbol.asyncIterator ]();
	}

	output.write( `${ message } (y/N) ` );
	// The reader closes at end of input, and a closed reader cannot be resumed or paused.
	if ( ! closed ) {
		readline.resume();
	}
	const { value, done } = await lines.next();
	if ( done ) {
		output.write( '\n' );
		return false;
	}
	// Stop reading between prompts so input meant for git, such as a credential prompt, reaches git.
	if ( ! closed ) {
		readline.pause();
	}

	return /^y(es)?$/i.test( value.trim() );
}

/**
 * Releases the terminal so the process can exit.
 */
export function closePrompts() {
	if ( readline && ! closed ) {
		readline.close();
	}
}
