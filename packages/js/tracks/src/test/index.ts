import {
	afterEach,
	beforeEach,
	describe,
	expect,
	it,
	vi,
	type MockInstance,
} from 'vitest';

/**
 * Internal dependencies
 */
import { recordEvent } from '..';
const eventName = 'my_event_name';
const props = {
	test: 'test value',
};
vi.mock( '../utils', () => {
	const mock = {
		isDevelopmentMode: false,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
describe( 'recordEvent', () => {
	let windowSpy: MockInstance;
	const recordEventMock = vi.fn();
	beforeEach( () => {
		windowSpy = vi.spyOn( window, 'window', 'get' );
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
