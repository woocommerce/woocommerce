import { afterAll, afterEach, beforeAll } from 'vitest';

/**
 * External dependencies
 */
import { setupServer } from 'msw/node';
import { http, HttpResponse } from 'msw';

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
export { server, http, HttpResponse };
