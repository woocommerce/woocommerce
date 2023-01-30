import axios, { AxiosInstance } from 'axios';
import MockAdapter from 'axios-mock-adapter';
import { AxiosResponseInterceptor } from '../axios-response-interceptor';

describe( 'AxiosResponseInterceptor', () => {
	let apiResponseInterceptor: AxiosResponseInterceptor;
	let axiosInstance: AxiosInstance;
	let adapter: MockAdapter;

	beforeEach( () => {
		axiosInstance = axios.create();
		adapter = new MockAdapter( axiosInstance );
		apiResponseInterceptor = new AxiosResponseInterceptor();
		apiResponseInterceptor.start( axiosInstance );
	} );

	afterEach( () => {
		apiResponseInterceptor.stop( axiosInstance );
		adapter.restore();
	} );

	it( 'should transform responses into an HTTPResponse', async () => {
		adapter
			.onGet( 'http://test.test' )
			.reply(
				200,
				{ test: 'value' },
				{ 'content-type': 'application/json' }
			);

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
		adapter
			.onGet( 'http://test.test' )
			.reply(
				404,
				{ code: 'error_code', message: 'value' },
				{ 'content-type': 'application/json' }
			);

		await expect(
			axiosInstance.get( 'http://test.test' )
		).rejects.toMatchObject( {
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
		adapter.onGet( 'http://test.test' ).timeout();

		await expect(
			axiosInstance.get( 'http://test.test' )
		).rejects.toMatchObject( new Error( 'timeout of 0ms exceeded' ) );
	} );
} );
