import * as moxios from 'moxios';
import { AxiosClient } from '../axios-client';
import { AxiosResponseInterceptor } from '../axios-response-interceptor';
import { HTTPResponse } from '../../http-client';

describe( 'AxiosClient', () => {
	let httpClient: AxiosClient;

	beforeEach( () => {
		moxios.install();
	} );

	afterEach( () => {
		moxios.uninstall();
	} );

	it( 'should execute interceptors', async () => {
		httpClient = new AxiosClient(
			{ baseURL: 'http://test.test' },
			[ new AxiosResponseInterceptor() ],
		);

		moxios.stubOnce( 'GET', '/test', {
			status: 200,
			headers: {
				'Content-Type': 'application/json',
			},
			responseText: JSON.stringify( { test: 'value' } ),
		} );

		const response = await httpClient.get( '/test' );
		expect( response ).toBeInstanceOf( HTTPResponse );
	} );
} );
