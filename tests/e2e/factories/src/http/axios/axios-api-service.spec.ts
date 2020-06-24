import axios, { AxiosInstance } from 'axios';
import moxios from 'moxios';
import { APIResponse, APIError } from './../api-service';
import { AxiosAPIService } from './axios-api-service';

describe( 'AxiosAPIService', () => {
	let apiClient: AxiosAPIService;

	beforeEach( () => {
		moxios.install();
		apiClient = new AxiosAPIService(
			'https://test.test/wp-json/',
			'consumer_key',
			'consumer_secret'
		);
	} );

	afterEach( () => {
		moxios.uninstall();
	} );

	it( 'should add interceptors', async () => {
		moxios.stubOnce( 'GET', '/wc/v2/product', {
			status: 200,
			headers: {
				'Content-Type': 'application/json',
			},
			responseText: JSON.stringify( { test: 'value' } ),
		} );

		const response = await apiClient.get( '/wc/v2/product' );
		expect( response ).toBeInstanceOf( APIResponse );

		const request = moxios.requests.mostRecent();
		expect( request.headers ).toHaveProperty( 'Authorization' );
	} );
} );
