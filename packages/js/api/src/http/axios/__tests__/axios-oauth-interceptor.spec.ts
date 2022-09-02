import axios, { AxiosInstance } from 'axios';
import MockAdapter from 'axios-mock-adapter';
import { AxiosOAuthInterceptor } from '../axios-oauth-interceptor';

describe( 'AxiosOAuthInterceptor', () => {
	let apiAuthInterceptor: AxiosOAuthInterceptor;
	let axiosInstance: AxiosInstance;
	let adapter: MockAdapter;

	beforeEach( () => {
		axiosInstance = axios.create();
		adapter = new MockAdapter( axiosInstance );
		apiAuthInterceptor = new AxiosOAuthInterceptor(
			'consumer_key',
			'consumer_secret'
		);
		apiAuthInterceptor.start( axiosInstance );
	} );

	afterEach( () => {
		apiAuthInterceptor.stop( axiosInstance );
		adapter.restore();
	} );

	it( 'should use basic auth for HTTPS', async () => {
		adapter.onGet( 'https://api.test' ).reply( 200 );
		const response = await axiosInstance.get( 'https://api.test' );

		expect( response.config.auth ).not.toBeNull();
		expect( response.config.auth!.username ).toBe( 'consumer_key' );
		expect( response.config.auth!.password ).toBe( 'consumer_secret' );
	} );

	it( 'should use OAuth 1.0a for HTTP', async () => {
		adapter.onGet( 'http://api.test' ).reply( 200 );
		const response = await axiosInstance.get( 'http://api.test' );

		// We're going to assume that the oauth-1.0a package added the signature data correctly so we will
		// focus on ensuring that the header looks roughly correct given what we readily know.
		expect( response.config.headers! ).toHaveProperty( 'Authorization' );
		expect( response.config.headers!.Authorization ).toMatch(
			/^OAuth oauth_consumer_key="consumer_key".*oauth_signature_method="HMAC-SHA256".*oauth_version="1.0"/
		);
	} );

	it( 'should work with base URL', async () => {
		adapter.onGet( 'https://api.test/test' ).reply( 200 );
		const response = await axiosInstance.request( {
			method: 'GET',
			baseURL: 'https://api.test/',
			url: '/test',
		} );

		expect( response.config.auth ).not.toBeNull();
		expect( response.config.auth!.username ).toBe( 'consumer_key' );
		expect( response.config.auth!.password ).toBe( 'consumer_secret' );
	} );
} );
