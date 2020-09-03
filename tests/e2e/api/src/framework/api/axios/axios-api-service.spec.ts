import moxios from 'moxios';
import { APIResponse } from '../api-service';
import { AxiosAPIService } from './axios-api-service';

describe( 'AxiosAPIService', () => {
	let apiClient: AxiosAPIService;

	beforeEach( () => {
		moxios.install();
	} );

	afterEach( () => {
		moxios.uninstall();
	} );

	it( 'should add OAuth interceptors', async () => {
		apiClient = AxiosAPIService.createUsingOAuth(
			'http://test.test/wp-json/',
			'consumer_key',
			'consumer_secret',
		);

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
		expect( request.headers.Authorization ).toMatch( /^OAuth/ );
	} );

	it( 'should add basic auth interceptors', async () => {
		apiClient = AxiosAPIService.createUsingBasicAuth( 'http://test.test/wp-json/', 'test', 'pass' );

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
		expect( request.headers.Authorization ).toMatch( /^Basic/ );
	} );
} );
