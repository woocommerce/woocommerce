import axios, { AxiosInstance } from 'axios';
import moxios from 'moxios';
import { APIResponse, APIError } from './../api-service';
import { APIResponseInterceptor } from './api-response-interceptor';

describe( 'APIResponseInterceptor', () => {
	let apiResponseInterceptor: APIResponseInterceptor;
	let axiosInstance: AxiosInstance;

	beforeEach( () => {
		axiosInstance = axios.create();
		moxios.install( axiosInstance );
		apiResponseInterceptor = new APIResponseInterceptor( axiosInstance );
		apiResponseInterceptor.start();
	} );

	afterEach( () => {
		apiResponseInterceptor.stop();
		moxios.uninstall();
	} );

	it( 'should transform responses into APIResponse', async () => {
		moxios.stubOnce( 'GET', 'http://test.test', {
			status: 200,
			headers: {
				'Content-Type': 'application/json',
			},
			responseText: JSON.stringify( { test: 'value' } ),
		} );

		const response = await axiosInstance.get( 'http://test.test' );

		expect( response ).toMatchObject( {
			status: 200,
			headers: {
				'content-type': 'application/json',
			},
			data: {
				test: 'value',
			},
		} );
	} );

	it( 'should transform response errors into APIError', async () => {
		moxios.stubOnce( 'GET', 'http://test.test', {
			status: 404,
			headers: {
				'Content-Type': 'application/json',
			},
			responseText: JSON.stringify( { code: 'error_code', message: 'value', data: null } ),
		} );

		await expect( axiosInstance.get( 'http://test.test' ) ).rejects.toMatchObject(
			new APIResponse(
				404,
				{ 'content-type': 'application/json' },
				new APIError( 'error_code', 'value', null ),
			),
		);
	} );
} );
