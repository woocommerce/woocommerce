import axios, { AxiosInstance } from 'axios';
import * as moxios from 'moxios';
import { AxiosInterceptor } from '../axios-interceptor';

class TestInterceptor extends AxiosInterceptor {}

describe( 'AxiosInterceptor', () => {
	let interceptors: TestInterceptor[];
	let axiosInstance: AxiosInstance;

	beforeEach( () => {
		axiosInstance = axios.create();
		moxios.install( axiosInstance );
		interceptors = [];
	} );

	afterEach( () => {
		for ( const interceptor of interceptors ) {
			interceptor.stop( axiosInstance );
		}
		moxios.uninstall( axiosInstance );
	} );

	it( 'should not break interceptor chaining for success', async () => {
		moxios.stubRequest( 'http://test.test', { status: 200 } );

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
		moxios.stubRequest( 'http://test.test', { status: 401 } );

		interceptors.push( new TestInterceptor() );
		interceptors.push( new TestInterceptor() );
		interceptors.push( new TestInterceptor() );
		for ( const interceptor of interceptors ) {
			interceptor.start( axiosInstance );
		}

		await expect( axiosInstance.get( 'http://test.test' ) ).rejects.toBeInstanceOf( Error );
	} );
} );
