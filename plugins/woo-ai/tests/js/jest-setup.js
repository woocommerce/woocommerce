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

window.HTMLElement.prototype.scrollIntoView = jest.fn();
const localStorageMock = {
	getItem: jest.fn(),
	setItem: jest.fn(),
	clear: jest.fn(),
};
global.localStorage = localStorageMock;
