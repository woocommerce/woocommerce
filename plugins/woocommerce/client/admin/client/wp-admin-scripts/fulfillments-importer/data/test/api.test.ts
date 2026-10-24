/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

/**
 * Internal dependencies
 */
import { prepare, runChunk } from '../api';

const mockedApiFetch = apiFetch as jest.MockedFunction< typeof apiFetch >;

describe( 'runChunk', () => {
	beforeEach( () => {
		mockedApiFetch.mockReset();
		mockedApiFetch.mockResolvedValue( {} );
	} );

	it( 'keeps unassigned and "Do not import" columns off the wire', async () => {
		await runChunk( {
			token: 'tok',
			offset: 0,
			limit: 100,
			mapping: {
				0: 'order_number',
				1: 'skip',
				2: '',
				3: 'tracking_number',
			},
			notifyCustomer: false,
			updateExisting: true,
		} );

		expect( mockedApiFetch ).toHaveBeenCalledTimes( 1 );
		const request = mockedApiFetch.mock.calls[ 0 ][ 0 ] as {
			data: { mapping: Record< string, string > };
		};
		expect( request.data.mapping ).toEqual( {
			0: 'order_number',
			3: 'tracking_number',
		} );
	} );

	it( 'sends the token, offset, limit and options through', async () => {
		await runChunk( {
			token: 'tok',
			offset: 200,
			limit: 100,
			mapping: { 0: 'order_number' },
			notifyCustomer: true,
			updateExisting: false,
		} );

		const request = mockedApiFetch.mock.calls[ 0 ][ 0 ] as {
			data: Record< string, unknown >;
		};
		expect( request.data.token ).toBe( 'tok' );
		expect( request.data.offset ).toBe( 200 );
		expect( request.data.limit ).toBe( 100 );
		expect( request.data.options ).toEqual( {
			notify_customer: true,
			update_existing: false,
		} );
	} );
} );

describe( 'prepare', () => {
	beforeEach( () => {
		mockedApiFetch.mockReset();
		mockedApiFetch.mockResolvedValue( {} );
	} );

	it( 'passes the abort signal through to apiFetch', async () => {
		const controller = new AbortController();

		await prepare( {
			file: new File( [ 'a,b' ], 'a.csv', { type: 'text/csv' } ),
			delimiter: ',',
			notifyCustomer: false,
			updateExisting: true,
			signal: controller.signal,
		} );

		expect( mockedApiFetch ).toHaveBeenCalledTimes( 1 );
		const request = mockedApiFetch.mock.calls[ 0 ][ 0 ] as {
			path: string;
			method: string;
			signal?: AbortSignal;
		};
		expect( request.path ).toBe( '/wc/v3/fulfillments/import/prepare' );
		expect( request.method ).toBe( 'POST' );
		expect( request.signal ).toBe( controller.signal );
	} );
} );
