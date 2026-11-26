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
	const originalTracks = window.wcTracks;
	const recordEventMock = jest.fn();

	beforeEach( () => {
		recordEventMock.mockClear();
		window.wcTracks = {
			...originalTracks,
			recordEvent: recordEventMock,
		};
	} );

	afterEach( () => {
		window.wcTracks = originalTracks;
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
