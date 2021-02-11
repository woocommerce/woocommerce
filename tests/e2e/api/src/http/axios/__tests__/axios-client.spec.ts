import * as moxios from 'moxios';
import { AxiosClient } from '../axios-client';
import { HTTPResponse } from '../../http-client';
import { AxiosInterceptor } from '../axios-interceptor';
import { mock } from 'jest-mock-extended';

describe( 'AxiosClient', () => {
	let httpClient: AxiosClient;

	beforeEach( () => {
		moxios.install();
	} );

	afterEach( () => {
		moxios.uninstall();
	} );

	it( 'should transform to HTTPResponse', async () => {
		httpClient = new AxiosClient( { baseURL: 'http://test.test' } );

		moxios.stubRequest( '/test', {
			status: 200,
			headers: {
				'Content-Type': 'application/json',
			},
			responseText: JSON.stringify( { test: 'value' } ),
		} );

		const response = await httpClient.get( '/test' );
		expect( response ).toBeInstanceOf( HTTPResponse );
		expect( response ).toHaveProperty( 'statusCode', 200 );
		expect( response ).toHaveProperty( 'headers', { 'content-type': 'application/json' } );
		expect( response ).toHaveProperty( 'data', { test: 'value' } );
	} );

	it( 'should start extra interceptors', async () => {
		const interceptor = mock< AxiosInterceptor >();

		httpClient = new AxiosClient(
			{ baseURL: 'http://test.test' },
			[ interceptor ],
		);

		moxios.stubRequest( '/test', {
			status: 200,
			headers: {
				'Content-Type': 'application/json',
			},
			responseText: JSON.stringify( { test: 'value' } ),
		} );

		await httpClient.get( '/test' );

		expect( interceptor.start ).toHaveBeenCalled();
	} );
} );
