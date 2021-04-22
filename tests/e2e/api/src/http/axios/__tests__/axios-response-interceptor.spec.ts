import axios, { AxiosInstance } from 'axios';
import * as moxios from 'moxios';
import { AxiosResponseInterceptor } from '../axios-response-interceptor';

describe( 'AxiosResponseInterceptor', () => {
	let apiResponseInterceptor: AxiosResponseInterceptor;
	let axiosInstance: AxiosInstance;

	beforeEach( () => {
		axiosInstance = axios.create();
		moxios.install( axiosInstance );
		apiResponseInterceptor = new AxiosResponseInterceptor();
		apiResponseInterceptor.start( axiosInstance );
	} );

	afterEach( () => {
		apiResponseInterceptor.stop( axiosInstance );
		moxios.uninstall();
	} );

	it( 'should transform responses into an HTTPResponse', async () => {
		moxios.stubRequest( 'http://test.test', {
			status: 200,
			headers: {
				'Content-Type': 'application/json',
			},
			responseText: JSON.stringify( { test: 'value' } ),
		} );

		const response = await axiosInstance.get( 'http://test.test' );

		expect( response ).toMatchObject( {
			statusCode: 200,
			headers: {
				'content-type': 'application/json',
			},
			data: {
				test: 'value',
			},
		} );
	} );

	it( 'should transform error responses into an HTTPResponse', async () => {
		moxios.stubRequest( 'http://test.test', {
			status: 404,
			headers: {
				'Content-Type': 'application/json',
			},
			responseText: JSON.stringify( { code: 'error_code', message: 'value' } ),
		} );

		await expect( axiosInstance.get( 'http://test.test' ) ).rejects.toMatchObject( {
			statusCode: 404,
			headers: {
				'content-type': 'application/json',
			},
			data: {
				code: 'error_code',
				message: 'value',
			},
		} );
	} );

	it( 'should bubble non-response errors', async () => {
		moxios.stubTimeout( 'http://test.test' );

		await expect( axiosInstance.get( 'http://test.test' ) ).rejects.toMatchObject(
			new Error( 'timeout of 0ms exceeded' ),
		);
	} );
} );
