import axios, { AxiosInstance } from 'axios';
import MockAdapter from 'axios-mock-adapter';
import { AxiosInterceptor } from '../axios-interceptor';

class TestInterceptor extends AxiosInterceptor {}

describe( 'AxiosInterceptor', () => {
	let interceptors: TestInterceptor[];
	let axiosInstance: AxiosInstance;
	let adapter: MockAdapter;

	beforeEach( () => {
		axiosInstance = axios.create();
		adapter = new MockAdapter( axiosInstance );
		interceptors = [];
	} );

	afterEach( () => {
		for ( const interceptor of interceptors ) {
			interceptor.stop( axiosInstance );
		}
		adapter.restore();
	} );

	it( 'should not break interceptor chaining for success', async () => {
		adapter.onGet( 'http://test.test' ).reply( 200 );

		interceptors.push( new TestInterceptor() );
		interceptors.push( new TestInterceptor() );
		interceptors.push( new TestInterceptor() );
		for ( const interceptor of interceptors ) {
			interceptor.start( axiosInstance );
		}

		const response = await axiosInstance.get( 'http://test.test' );

		expect( response.status ).toBe( 200 );
	} );

	it( 'should not break interceptor chaining for errors', async () => {
		adapter.onGet( 'http://test.test' ).reply( 401 );

		interceptors.push( new TestInterceptor() );
		interceptors.push( new TestInterceptor() );
		interceptors.push( new TestInterceptor() );
		for ( const interceptor of interceptors ) {
			interceptor.start( axiosInstance );
		}

		await expect(
			axiosInstance.get( 'http://test.test' )
		).rejects.toBeInstanceOf( Error );
	} );
} );
