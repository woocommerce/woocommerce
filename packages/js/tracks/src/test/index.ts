/**
 * Internal dependencies
 */
import { recordEvent } from '..';

const eventName = 'my_event_name';
const props = {
	test: 'test value',
};

jest.mock( '../utils', () => ( {
	isDevelopmentMode: false,
} ) );

describe( 'recordEvent', () => {
	let windowSpy: jest.SpyInstance;
	const recordEventMock = jest.fn();

	beforeEach( () => {
		windowSpy = jest.spyOn( window, 'window', 'get' );
		windowSpy.mockImplementation( () => ( {
			wcTracks: {
				recordEvent: recordEventMock,
			},
		} ) );
	} );

	afterEach( () => {
		windowSpy.mockRestore();
	} );

	it( 'should record an event without props', () => {
		recordEvent( eventName, {} );
		expect( recordEventMock ).toHaveBeenCalledWith( eventName, {} );
	} );

	it( 'should record an event with props', () => {
		recordEvent( eventName, props );
		expect( recordEventMock ).toHaveBeenCalledWith( eventName, props );
	} );
} );
