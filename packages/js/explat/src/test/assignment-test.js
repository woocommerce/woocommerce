// Define that tracking is enabled before import
// so that assignments can get the correct value.
global.wcTracks.isEnabled = true;

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
			experimentName: '123',
			anonId: 'abc',
		} );
		Promise.resolve( fetchPromise );

		expect( fetchMock ).toHaveBeenCalledWith(
			'https://public-api.wordpress.com/wpcom/v2/experiments/0.1.0/assignments/woocommerce?experiment_name=123&anon_id=abc&test=test'
		);
	} );

	it( 'should throw error when anonId is empty', async () => {
		const fetchPromise = fetchExperimentAssignment( {
			experimentName: '123',
			anonId: null,
		} );
		await expect( fetchPromise ).rejects.toThrowError();
	} );

	it( 'should throw error when experiment_name is empty', async () => {
		const fetchPromise = fetchExperimentAssignment( {
			experimentName: '',
			anonId: null,
		} );
		await expect( fetchPromise ).rejects.toThrowError();
	} );

	it( 'should throw error when experiment_name is invalid', async () => {
		const fetchPromise = fetchExperimentAssignment( {
			experimentName: '',
			anonId: null,
		} );
		await expect( fetchPromise ).rejects.toThrowError();
	} );

	it( 'should return .json response', async () => {
		const data = {
			variations: { woocommerce_test: null },
			ttl: 60,
			debug: { backend_aa_result: 'request not sampled' },
		};
		window.fetch.mockImplementation( () =>
			Promise.resolve( {
				json: () => Promise.resolve( data ),
				status: 200,
			} )
		);

		const assignment = await fetchExperimentAssignment( {
			experimentName: 'woocommerce_test',
			anonId: '1234',
		} );
		await expect( assignment ).toEqual( data );
	} );

	it( 'adds woo_wcadmin_install_timestamp to request args', () => {
		const filterArgs = { args: {} };
		addFilter(
			'woocommerce_explat_request_args',
			'woo_wcadmin_install_timestamp_test',
			function ( args ) {
				filterArgs.args = args;
				return args;
			}
		);

		const fetchPromise = fetchExperimentAssignmentWithAuth( {
			experimentName: '123',
			anonId: 'abc',
		} );
		Promise.resolve( fetchPromise );

		expect( filterArgs.args ).toHaveProperty(
			'woo_wcadmin_install_timestamp'
		);
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
			experimentName: '123',
			anonId: 'abc',
		} );
		Promise.resolve( fetchPromise );

		expect( fetchMock ).toHaveBeenCalledWith(
			'/wc-admin/experiments/assignment?experiment_name=123&anon_id=abc&test=test&_locale=user',
			{
				body: undefined,
				credentials: 'include',
				headers: { Accept: 'application/json, */*;q=0.1' },
			}
		);
	} );

	it( 'adds woo_wcadmin_install_timestamp to request args', () => {
		const filterArgs = { args: {} };
		addFilter(
			'woocommerce_explat_request_args',
			'woo_wcadmin_install_timestamp_test',
			function ( args ) {
				filterArgs.args = args;
				return args;
			}
		);

		const fetchPromise = fetchExperimentAssignmentWithAuth( {
			experimentName: '123',
			anonId: 'abc',
		} );
		Promise.resolve( fetchPromise );

		expect( filterArgs.args ).toHaveProperty(
			'woo_wcadmin_install_timestamp'
		);
	} );
} );
