/**
 * External dependencies
 */
const { setupServer } = require( 'msw/node' );
const { http, HttpResponse } = require( 'msw' );

// Create MSW server instance for testing
const server = setupServer();

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

// Export utilities for use in tests
module.exports = { server, http, HttpResponse };
