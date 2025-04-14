/**
 * External dependencies
 */
import axios from 'axios';
import MockAdapter from 'axios-mock-adapter';

/**
 * Internal dependencies
 */
import { AxiosClient } from '../axios-client';
import { HTTPResponse } from '../../http-client';
import { AxiosInterceptor } from '../axios-interceptor';

class DummyInterceptor extends AxiosInterceptor {
	public start = jest.fn();
	public stop = jest.fn();
}

describe( 'AxiosClient', () => {
	let httpClient: AxiosClient;

	it( 'should transform to HTTPResponse', async () => {
		const adapter = new MockAdapter( axios );

		httpClient = new AxiosClient( { baseURL: 'http://test.test' } );

		adapter
			.onGet( '/test' )
			.reply(
				200,
				{ test: 'value' },
				{ 'content-type': 'application/json' }
			);

		const response = await httpClient.get( '/test' );

		adapter.restore();

		expect( response ).toBeInstanceOf( HTTPResponse );
		expect( response ).toHaveProperty( 'statusCode', 200 );
		expect( response ).toHaveProperty( 'headers', {
			'content-type': 'application/json',
		} );
		expect( response ).toHaveProperty( 'data', { test: 'value' } );
	} );

	it( 'should start extra interceptors', async () => {
		const interceptor = new DummyInterceptor();

		const adapter = new MockAdapter( axios );

		httpClient = new AxiosClient( { baseURL: 'http://test.test' }, [
			interceptor,
		] );

		adapter.onGet( '/test' ).reply( 200, { test: 'value' } );

		await httpClient.get( '/test' );

		adapter.restore();

		expect( interceptor.start ).toHaveBeenCalled();
	} );
} );
