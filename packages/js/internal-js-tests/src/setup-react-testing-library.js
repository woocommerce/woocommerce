/**
 * External dependencies
 */
import '@wordpress/jest-console';
import '@testing-library/jest-dom';

/**
 * Ignore messages that match the pattern `Store ".*" is already registered.`
 * It's expected to have duplicate registration in the test environment as there are multiple instances of the same store such as core-data, core/block-editor, etc.
 */
const consoleErrorSpy = jest
	.spyOn( console, 'error' )
	.mockImplementation( ( message, ...args ) => {
		if (
			typeof message === 'string' &&
			// The error was introduced since @wordpress/data@8.6.0.
			message.match( /Store ".*" is already registered/ )
		) {
			return;
		}
		// Otherwise, call the original console.error
		consoleErrorSpy.mock.calls.push( [ message, ...args ] );
	} );
