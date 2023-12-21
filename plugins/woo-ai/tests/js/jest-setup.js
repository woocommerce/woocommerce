/**
 * External dependencies
 */
import '@testing-library/jest-dom';

beforeEach( () => {
	// Mock fetch
	global.fetch = jest.fn( () =>
		Promise.resolve( {
			json: () => Promise.resolve( {} ),
		} )
	);
} );

afterEach( () => {
	jest.clearAllTimers();
} );

global.ResizeObserver = require( 'resize-observer-polyfill' );
