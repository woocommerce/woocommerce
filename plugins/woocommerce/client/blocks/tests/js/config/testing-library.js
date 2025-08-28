/**
 * External dependencies
 */
require( '@testing-library/jest-dom' );

function overrideConsoleSpies() {
	// Restore original console methods if they were spied on
	const consoleMethods = [ 'error', 'info', 'log', 'warn' ];

	consoleMethods.forEach( ( method ) => {
		const spy = console[ method ];
		if ( spy && typeof spy.mockRestore === 'function' ) {
			spy.mockRestore();
		}
	} );

	// Set up your custom console handling
	consoleMethods.forEach( ( method ) => {
		const spy = jest.spyOn( console, method ).mockImplementation( () => {
			// Your custom logic here
			// For example: allow all console calls without failing tests
		} );

		// Add any custom beforeEach/afterEach logic you need
		beforeEach( () => {
			spy.mockClear();
		} );
	} );
}

// Call the override function
overrideConsoleSpies();
