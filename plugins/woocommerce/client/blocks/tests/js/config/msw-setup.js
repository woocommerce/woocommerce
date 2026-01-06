/**
 * External dependencies
 */
let server, http, HttpResponse;

try {
	const mswNode = require( 'msw/node' );
	const msw = require( 'msw' );

	( { setupServer } = mswNode );
	( { http, HttpResponse } = msw );

	// Create MSW server instance for testing
	server = setupServer();

	// Setup MSW for all tests
	beforeAll( () => {
		// Start the server before all tests
		server.listen( {
			onUnhandledRequest: 'bypass', // Allow unhandled requests to pass through
		} );
	} );

	afterEach( () => {
		// Reset any runtime request handlers after each test
		server.resetHandlers();
	} );

	afterAll( () => {
		// Clean up after all tests are done
		server.close();
	} );
} catch ( error ) {
	// MSW is not installed or not available - tests that don't need it can still run
	server = null;
	http = null;
	HttpResponse = null;
}

// Export utilities for use in tests
module.exports = { server, http, HttpResponse };
