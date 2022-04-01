import axios, { AxiosInstance } from 'axios';
import * as moxios from 'moxios';
import { AxiosURLToQueryInterceptor } from '../axios-url-to-query-interceptor';

describe( 'AxiosURLToQueryInterceptor', () => {
	let urlToQueryInterceptor: AxiosURLToQueryInterceptor;
	let axiosInstance: AxiosInstance;

	beforeEach( () => {
		axiosInstance = axios.create();
		moxios.install( axiosInstance );
		urlToQueryInterceptor = new AxiosURLToQueryInterceptor( 'test' );
		urlToQueryInterceptor.start( axiosInstance );
	} );

	afterEach( () => {
		urlToQueryInterceptor.stop( axiosInstance );
		moxios.uninstall();
	} );

	it( 'should put path in query string', async () => {
		moxios.stubRequest( 'http://test.test/?test=%2Ftest%2Froute', {
			status: 200,
			headers: {
				'Content-Type': 'application/json',
			},
			responseText: JSON.stringify( { test: 'value' } ),
		} );

		const response = await axiosInstance.get( 'http://test.test/test/route' );

		expect( response.status ).toEqual( 200 );
	} );
} );
