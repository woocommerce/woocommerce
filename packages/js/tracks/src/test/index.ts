/**
 * Internal dependencies
 */
import { recordEvent } from '..';

const eventName = 'my_event_name';
const props = {
	test: 'test value',
};

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

	it( 'should not record an event without name', () => {
		const response = recordEvent( '', props );
		expect( response ).toBe( false );
		expect( recordEventMock ).not.toHaveBeenCalledWith();
	} );

	it( 'should not record an event with an invalid name', () => {
		const nameWithDash = recordEvent( 'event-name', props );
		const nameWithSpace = recordEvent( 'event name', props );
		const singleName = recordEvent( 'eventName', props );
		const nameWithSlash = recordEvent( 'event_name/', props );
		expect( nameWithDash ).toBe( false );
		expect( nameWithSpace ).toBe( false );
		expect( singleName ).toBe( false );
		expect( nameWithSlash ).toBe( false );
		expect( recordEventMock ).not.toHaveBeenCalledWith();
	} );

	it( 'should not record an event with invalid props name', () => {
		const propWithDash = recordEvent( eventName, {
			'prop-name': 'value',
		} );
		const propWithSpace = recordEvent( eventName, {
			'prop name': 'value',
		} );
		const propWithSlash = recordEvent( eventName, {
			'propName/': 'value',
		} );
		expect( propWithDash ).toBe( false );
		expect( propWithSpace ).toBe( false );
		expect( propWithSlash ).toBe( false );
		expect( recordEventMock ).not.toHaveBeenCalledWith();
	} );
} );
