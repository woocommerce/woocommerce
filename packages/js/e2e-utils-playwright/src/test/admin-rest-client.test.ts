/**
 * External dependencies
 */
import type { APIRequestContext } from '@playwright/test';

/**
 * Internal dependencies
 */
import { createAdminRestClient } from '../admin-rest-client';

function mockAdminRequest() {
	const dispose = jest.fn().mockResolvedValue( undefined );
	const nonceResponse = {
		ok: () => true,
		status: () => 200,
		text: jest.fn().mockResolvedValue( ' 0123456789\n' ),
		dispose,
	};
	const get = jest.fn().mockResolvedValue( nonceResponse );
	const restResponse = { ok: () => true };
	const fetch = jest.fn().mockResolvedValue( restResponse );

	return {
		request: { get, fetch } as unknown as APIRequestContext,
		get,
		fetch,
		nonceResponse,
		restResponse,
	};
}

describe( 'createAdminRestClient', () => {
	it( 'uses the same admin session and one nonce for concurrent WooCommerce requests', async () => {
		const { request, get, fetch, nonceResponse, restResponse } =
			mockAdminRequest();
		const send = createAdminRestClient(
			request,
			'https://example.com/shop'
		);
		const options = { method: 'PUT', data: { status: 'completed' } };

		const responses = await Promise.all( [
			send( 'orders/12', options ),
			send( '/products' ),
		] );

		expect( get ).toHaveBeenCalledTimes( 1 );
		expect( get ).toHaveBeenCalledWith(
			'https://example.com/shop/wp-admin/admin-ajax.php?action=rest-nonce',
			{ maxRedirects: 0 }
		);
		expect( nonceResponse.dispose ).toHaveBeenCalledTimes( 1 );
		expect( fetch ).toHaveBeenCalledWith(
			'https://example.com/shop/wp-json/wc/v3/orders/12',
			{
				...options,
				headers: { 'X-WP-Nonce': '0123456789' },
				maxRedirects: 0,
			}
		);
		expect( fetch ).toHaveBeenCalledWith(
			'https://example.com/shop/wp-json/wc/v3/products',
			{
				headers: { 'X-WP-Nonce': '0123456789' },
				maxRedirects: 0,
			}
		);
		expect( responses ).toEqual( [ restResponse, restResponse ] );
	} );

	it( 'rejects an invalid nonce response and lets a later call fetch a new nonce', async () => {
		const { request, get, fetch, nonceResponse, restResponse } =
			mockAdminRequest();
		nonceResponse.text.mockResolvedValueOnce( '0' );
		const send = createAdminRestClient( request, 'https://example.com' );

		await expect( send( 'products' ) ).rejects.toThrow( 'REST nonce' );
		expect( fetch ).not.toHaveBeenCalled();
		expect( nonceResponse.dispose ).toHaveBeenCalledTimes( 1 );

		await expect( send( 'products' ) ).resolves.toBe( restResponse );
		expect( get ).toHaveBeenCalledTimes( 2 );
	} );

	it.each( [ 'https://other.example/orders', '../../wp/v2/users' ] )(
		'rejects a route outside the WooCommerce REST API: %s',
		async ( route ) => {
			const { request, get, fetch } = mockAdminRequest();
			const send = createAdminRestClient(
				request,
				'https://example.com'
			);

			await expect( send( route ) ).rejects.toThrow(
				'within the WooCommerce REST API'
			);
			expect( get ).not.toHaveBeenCalled();
			expect( fetch ).not.toHaveBeenCalled();
		}
	);
} );
