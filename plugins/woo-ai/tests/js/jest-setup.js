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

// Mock the global MediaRecorder so we can create a test audioBlob for transcriptions.
global.navigator.mediaDevices = {
	getUserMedia: jest.fn().mockResolvedValue( {} ),
};
global.MediaRecorder = function ( stream ) {
	this.stream = stream;
	this.state = 'inactive'; // Initial state
	this.start = jest.fn( () => {
		this.state = 'recording'; // Change state to recording when start is called
	} );
	this.stop = jest.fn( () => {
		this.state = 'inactive'; // Change state back to inactive when stop is called
	} );
	this.addEventListener = jest.fn( ( event, handler ) => {
		if ( event === 'dataavailable' ) {
			setTimeout(
				() =>
					handler( {
						data: new Blob( [ 'test data' ], {
							type: 'audio/wav',
						} ),
					} ),
				0
			);
		}
	} );
	this.removeEventListener = jest.fn();
};
