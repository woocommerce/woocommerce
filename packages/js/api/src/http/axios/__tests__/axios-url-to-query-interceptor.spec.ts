import axios, { AxiosInstance } from 'axios';
import MockAdapter from 'axios-mock-adapter';
import { AxiosURLToQueryInterceptor } from '../axios-url-to-query-interceptor';

describe( 'AxiosURLToQueryInterceptor', () => {
	let urlToQueryInterceptor: AxiosURLToQueryInterceptor;
	let axiosInstance: AxiosInstance;
	let adapter: MockAdapter;

	beforeEach( () => {
		axiosInstance = axios.create();
		adapter = new MockAdapter( axiosInstance );
		urlToQueryInterceptor = new AxiosURLToQueryInterceptor( 'test' );
		urlToQueryInterceptor.start( axiosInstance );
	} );

	afterEach( () => {
		urlToQueryInterceptor.stop( axiosInstance );
		adapter.restore();
	} );

	it( 'should put path in query string', async () => {
		adapter
			.onGet( 'http://test.test/', { params: { test: '/test/route' } } )
			.reply(
				200,
				{ test: 'value' },
				{ 'content-type': 'application/json' }
			);

		const response = await axiosInstance.get(
			'http://test.test/test/route'
		);

		expect( response.status ).toEqual( 200 );
	} );
} );
