/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { createNoticesFromResponse } from '../../notices';

jest.mock( '@wordpress/data', () => ( {
	dispatch: jest.fn().mockReturnValue( {
		createNotice: jest.fn(),
	} ),
} ) );

describe( 'createNoticesFromResponse', () => {
	let originalOnLine;

	beforeAll( () => {
		originalOnLine = Object.getOwnPropertyDescriptor(
			window.navigator,
			'onLine'
		);
	} );

	beforeEach( () => {
		jest.clearAllMocks();
	} );

	afterEach( () => {
		if ( originalOnLine ) {
			Object.defineProperty( window.navigator, 'onLine', originalOnLine );
		} else {
			// When `onLine` lives on the prototype (e.g. jsdom), the own
			// descriptor is undefined. Delete any own override a test
			// defined so inherited prototype behavior is restored.
			delete window.navigator.onLine;
		}
	} );

	const { createNotice } = dispatch( 'core/notices' );

	test( 'should create notice based on message when no errors exist', () => {
		const response = { message: 'Generic response message' };

		createNoticesFromResponse( response );
		expect( createNotice ).toHaveBeenCalledWith(
			'success',
			response.message
		);
	} );

	test( 'should create an error notice when an error code and message exists', () => {
		const response = { code: 'invalid_code', message: 'Error message' };

		createNoticesFromResponse( response );
		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			response.message
		);
	} );

	test( 'should create error messages for each item', () => {
		const response = {
			errors: {
				item1: [ 'Item1 - Error 1.', 'Item1 - Error 2.' ],
				item2: [ 'Item2 - Error 1.' ],
			},
			error_data: [],
		};

		createNoticesFromResponse( response );
		expect( createNotice ).toHaveBeenCalledTimes( 2 );
		const call1 = createNotice.mock.calls[ 0 ];
		const call2 = createNotice.mock.calls[ 1 ];
		expect( call1 ).toEqual( [
			'error',
			response.errors.item1.join( ' ' ),
		] );
		expect( call2 ).toEqual( [ 'error', response.errors.item2[ 0 ] ] );
	} );

	test( 'should not call createNotice when no message or errors exist', () => {
		const response = { data: {} };

		createNoticesFromResponse( response );
		expect( createNotice ).not.toHaveBeenCalled();
	} );

	test( 'should surface a friendly offline notice when response is a raw TypeError', () => {
		const response = new TypeError( 'Failed to fetch' );

		createNoticesFromResponse( response );
		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			'Updating failed. You are probably offline.'
		);
		expect( createNotice ).toHaveBeenCalledTimes( 1 );
	} );

	test( 'should surface a friendly offline notice for an empty rejection while navigator is offline', () => {
		Object.defineProperty( window.navigator, 'onLine', {
			configurable: true,
			value: false,
		} );

		createNoticesFromResponse( {} );
		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			'Updating failed. You are probably offline.'
		);
		expect( createNotice ).toHaveBeenCalledTimes( 1 );
		// afterEach restores navigator.onLine.
	} );
} );
