/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import {
	fetchExperimentAssignment,
	fetchExperimentAssignmentWithAuth,
} from '../assignment';
global.fetch = jest.fn().mockImplementation( () =>
	Promise.resolve( {
		json: () => Promise.resolve( {} ),
		status: 200,
	} )
);
global.wcTracks.isEnabled = true;

const fetchMock = jest.spyOn( global, 'fetch' );

describe( 'fetchExperimentAssignment', () => {
	it( 'applies woocommerce_explat_request_args before constructing the full URL', () => {
		addFilter(
			'woocommerce_explat_request_args',
			'test',
			function ( args ) {
				args.test = 'test';
				return args;
			}
		);

		const fetchPromise = fetchExperimentAssignment( {
			experimentId: '123',
			anonId: 'abc',
		} );
		Promise.resolve( fetchPromise );

		expect( fetchMock ).toHaveBeenCalledWith(
			'https://public-api.wordpress.com/wpcom/v2/experiments/0.1.0/assignments/woocommerce?anon_id=abc&test=test'
		);
	} );

	it( 'should throw error when anonId is empty', async () => {
		const fetchPromise = fetchExperimentAssignment( {
			experimentId: '123',
			anonId: null,
		} );
		await expect( fetchPromise ).rejects.toThrowError();
	} );
} );

describe( 'fetchExperimentAssignmentWithAuth', () => {
	it( 'applies woocommerce_explat_request_args before constructing the full URL', () => {
		fetchMock.mockClear();
		addFilter(
			'woocommerce_explat_request_args',
			'test',
			function ( args ) {
				args.test = 'test';
				return args;
			}
		);

		const fetchPromise = fetchExperimentAssignmentWithAuth( {
			experimentId: '123',
			anonId: 'abc',
		} );
		Promise.resolve( fetchPromise );

		expect( fetchMock ).toHaveBeenCalledWith(
			'/wc-admin/experiments/assignment?anon_id=abc&test=test&_locale=user',
			{
				body: undefined,
				credentials: 'include',
				headers: { Accept: 'application/json, */*;q=0.1' },
			}
		);
	} );
} );
