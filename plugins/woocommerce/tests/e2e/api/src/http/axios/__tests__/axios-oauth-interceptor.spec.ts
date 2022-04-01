import axios, { AxiosInstance } from 'axios';
import * as moxios from 'moxios';
import { AxiosOAuthInterceptor } from '../axios-oauth-interceptor';

describe( 'AxiosOAuthInterceptor', () => {
	let apiAuthInterceptor: AxiosOAuthInterceptor;
	let axiosInstance: AxiosInstance;

	beforeEach( () => {
		axiosInstance = axios.create();
		moxios.install( axiosInstance );
		apiAuthInterceptor = new AxiosOAuthInterceptor(
			'consumer_key',
			'consumer_secret',
		);
		apiAuthInterceptor.start( axiosInstance );
	} );

	afterEach( () => {
		apiAuthInterceptor.stop( axiosInstance );
		moxios.uninstall( axiosInstance );
	} );

	it( 'should not run unless started', async () => {
		moxios.stubRequest( 'https://api.test', { status: 200 } );

		apiAuthInterceptor.stop( axiosInstance );
		await axiosInstance.get( 'https://api.test' );

		let request = moxios.requests.mostRecent();
		expect( request.headers ).not.toHaveProperty( 'Authorization' );

		apiAuthInterceptor.start( axiosInstance );
		await axiosInstance.get( 'https://api.test' );

		request = moxios.requests.mostRecent();
		expect( request.headers ).toHaveProperty( 'Authorization' );
	} );

	it( 'should use basic auth for HTTPS', async () => {
		moxios.stubRequest( 'https://api.test', { status: 200 } );
		await axiosInstance.get( 'https://api.test' );

		const request = moxios.requests.mostRecent();

		expect( request.headers ).toHaveProperty( 'Authorization' );
		expect( request.headers.Authorization ).toBe(
			'Basic ' +
				Buffer.from( 'consumer_key:consumer_secret' ).toString( 'base64' ),
		);
	} );

	it( 'should use OAuth 1.0a for HTTP', async () => {
		moxios.stubRequest( 'http://api.test', { status: 200 } );
		await axiosInstance.get( 'http://api.test' );

		const request = moxios.requests.mostRecent();

		// We're going to assume that the oauth-1.0a package added the signature data correctly so we will
		// focus on ensuring that the header looks roughly correct given what we readily know.
		expect( request.headers ).toHaveProperty( 'Authorization' );
		expect( request.headers.Authorization ).toMatch(
			/^OAuth oauth_consumer_key="consumer_key".*oauth_signature_method="HMAC-SHA256".*oauth_version="1.0"/,
		);
	} );

	it( 'should work with base URL', async () => {
		moxios.stubRequest( '/test', { status: 200 } );
		await axiosInstance.request( {
			method: 'GET',
			baseURL: 'https://api.test/',
			url: '/test',
		} );

		const request = moxios.requests.mostRecent();

		expect( request.headers ).toHaveProperty( 'Authorization' );
		expect( request.headers.Authorization ).toBe(
			'Basic ' +
				Buffer.from( 'consumer_key:consumer_secret' ).toString( 'base64' ),
		);
	} );
} );
