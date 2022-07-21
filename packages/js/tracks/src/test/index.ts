/**
 * Internal dependencies
 */
import { recordEvent } from '..';
import { validateEventNameAndProperties } from '../utils';

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

	it( 'should not pass the validation an event without name', () => {
		const response = validateEventNameAndProperties( '', props );
		expect( response ).toBe( false );
	} );

	it( 'should not pass the validation an event with an invalid name', () => {
		const nameWithDash = validateEventNameAndProperties(
			'event-name',
			props
		);
		const nameWithSpace = validateEventNameAndProperties(
			'event name',
			props
		);
		const singleName = validateEventNameAndProperties( 'eventName', props );
		const nameWithSlash = validateEventNameAndProperties(
			'event_name/',
			props
		);
		expect( nameWithDash ).toBe( false );
		expect( nameWithSpace ).toBe( false );
		expect( singleName ).toBe( false );
		expect( nameWithSlash ).toBe( false );
		expect( recordEventMock ).not.toHaveBeenCalledWith();
	} );

	it( 'should not pass the validation an event with invalid prop names', () => {
		const propWithDash = validateEventNameAndProperties( eventName, {
			'prop-name': 'value',
		} );
		const propWithSpace = validateEventNameAndProperties( eventName, {
			'prop name': 'value',
		} );
		const propWithSlash = validateEventNameAndProperties( eventName, {
			'propName/': 'value',
		} );
		expect( propWithDash ).toBe( false );
		expect( propWithSpace ).toBe( false );
		expect( propWithSlash ).toBe( false );
		expect( recordEventMock ).not.toHaveBeenCalledWith();
	} );
} );
