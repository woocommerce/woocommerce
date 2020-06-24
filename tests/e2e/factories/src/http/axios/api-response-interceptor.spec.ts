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
			responseText: JSON.stringify( { message: 'value' } ),
		} );

		await axiosInstance.get( 'http://test.test' ).catch( ( error ) => {
			expect( error ).toMatchObject(
				new APIError(
					new APIResponse(
						404,
						{ 'content-type': 'application/json' },
						{ message: 'value' },
					),
					null,
				),
			);
		} );
	} );
} );
