/**
 * External dependencies
 */
import type { APIRequestContext } from '@playwright/test';
import { createReadStream } from 'node:fs';

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
	const restResponse = { ok: () => true, status: () => 200 };
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
	it( 'shares one nonce across namespaces and preserves caller headers', async () => {
		const { request, get, fetch, nonceResponse, restResponse } =
			mockAdminRequest();
		const send = createAdminRestClient(
			request,
			'https://example.com/shop'
		);
		const options = {
			method: 'PUT',
			data: { status: 'completed' },
			headers: {
				'Content-Type': 'application/json',
				'x-wp-nonce': 'caller-nonce',
			},
		};

		const responses = await Promise.all( [
			send( 'wc/v3/orders/12', options ),
			send( '/wp/v2/pages?per_page=6' ),
		] );

		expect( get ).toHaveBeenCalledTimes( 1 );
		expect( get ).toHaveBeenCalledWith(
			'https://example.com/shop/wp-admin/admin-ajax.php?action=rest-nonce',
			{ maxRedirects: 0, failOnStatusCode: false }
		);
		expect( nonceResponse.dispose ).toHaveBeenCalledTimes( 1 );
		expect( fetch ).toHaveBeenCalledWith(
			'https://example.com/shop/wp-json/wc/v3/orders/12',
			{
				...options,
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': '0123456789',
				},
				maxRedirects: 0,
				failOnStatusCode: false,
			}
		);
		expect( fetch ).toHaveBeenCalledWith(
			'https://example.com/shop/wp-json/wp/v2/pages?per_page=6',
			{
				headers: { 'X-WP-Nonce': '0123456789' },
				maxRedirects: 0,
				failOnStatusCode: false,
			}
		);
		expect( responses ).toEqual( [ restResponse, restResponse ] );
	} );

	it( 'rejects an invalid nonce response and lets a later call fetch a new nonce', async () => {
		const { request, get, fetch, nonceResponse, restResponse } =
			mockAdminRequest();
		nonceResponse.text.mockResolvedValueOnce( '0' );
		const send = createAdminRestClient( request, 'https://example.com' );

		await expect( send( 'wc/v3/products' ) ).rejects.toThrow(
			'REST nonce'
		);
		expect( fetch ).not.toHaveBeenCalled();
		expect( nonceResponse.dispose ).toHaveBeenCalledTimes( 1 );

		await expect( send( 'wc/v3/products' ) ).resolves.toBe( restResponse );
		expect( get ).toHaveBeenCalledTimes( 2 );
	} );

	it( 'refreshes a stale nonce once for concurrent requests', async () => {
		const { request, get, fetch, nonceResponse, restResponse } =
			mockAdminRequest();
		nonceResponse.text
			.mockResolvedValueOnce( 'aaaaaaaaaa' )
			.mockResolvedValueOnce( 'bbbbbbbbbb' );
		const stale = {
			status: () => 403,
			json: jest.fn().mockResolvedValue( {
				code: 'rest_cookie_invalid_nonce',
			} ),
			dispose: jest.fn().mockResolvedValue( undefined ),
		};
		fetch.mockResolvedValueOnce( stale ).mockResolvedValueOnce( stale );
		const send = createAdminRestClient( request, 'https://example.com' );

		await expect(
			Promise.all( [
				send( 'wc-bookings/v1/products' ),
				send( 'wp/v2/pages', {
					method: 'POST',
					data: { title: 'Test' },
				} ),
			] )
		).resolves.toEqual( [ restResponse, restResponse ] );

		expect( get ).toHaveBeenCalledTimes( 2 );
		expect( stale.dispose ).toHaveBeenCalledTimes( 2 );
		expect(
			fetch.mock.calls.map( ( [ , options ] ) => options.headers )
		).toEqual( [
			{ 'X-WP-Nonce': 'aaaaaaaaaa' },
			{ 'X-WP-Nonce': 'aaaaaaaaaa' },
			{ 'X-WP-Nonce': 'bbbbbbbbbb' },
			{ 'X-WP-Nonce': 'bbbbbbbbbb' },
		] );
	} );

	it.each( [
		[ 'rest_cookie_invalid_nonce', 2 ],
		[ 'rest_forbidden', 1 ],
	] )(
		'returns HTTP 403 with %s after %i attempt(s)',
		async ( code, attempts ) => {
			const { request, get, fetch } = mockAdminRequest();
			const forbidden = {
				status: () => 403,
				json: jest.fn().mockResolvedValue( { code } ),
				dispose: jest.fn().mockResolvedValue( undefined ),
			};
			fetch.mockResolvedValue( forbidden );
			const send = createAdminRestClient(
				request,
				'https://example.com'
			);

			await expect( send( 'wp/v2/pages' ) ).resolves.toBe( forbidden );
			expect( get ).toHaveBeenCalledTimes( attempts );
			expect( fetch ).toHaveBeenCalledTimes( attempts );
			expect( forbidden.dispose ).toHaveBeenCalledTimes( attempts - 1 );
		}
	);

	it( 'returns the original error for an upload stream and refreshes on the next request', async () => {
		const { request, get, fetch, restResponse } = mockAdminRequest();
		const stale = {
			status: () => 403,
			json: jest.fn().mockResolvedValue( {
				code: 'rest_cookie_invalid_nonce',
			} ),
			dispose: jest.fn(),
		};
		fetch.mockResolvedValueOnce( stale );
		const send = createAdminRestClient( request, 'https://example.com' );
		const file = createReadStream( __filename );

		try {
			await expect(
				send( 'wp/v2/media', { method: 'POST', multipart: { file } } )
			).resolves.toBe( stale );
			expect( fetch ).toHaveBeenCalledTimes( 1 );
			expect( stale.dispose ).not.toHaveBeenCalled();
			await expect( send( 'wp/v2/pages' ) ).resolves.toBe( restResponse );
			expect( get ).toHaveBeenCalledTimes( 2 );
		} finally {
			file.destroy();
		}
	} );

	it( 'fetches a fresh nonce for a stream upload after the retry rejects its nonce', async () => {
		const { request, get, fetch, nonceResponse, restResponse } =
			mockAdminRequest();
		nonceResponse.text
			.mockResolvedValueOnce( 'aaaaaaaaaa' )
			.mockResolvedValueOnce( 'bbbbbbbbbb' )
			.mockResolvedValueOnce( 'cccccccccc' );
		const stale = {
			status: () => 403,
			json: jest.fn().mockResolvedValue( {
				code: 'rest_cookie_invalid_nonce',
			} ),
			dispose: jest.fn().mockResolvedValue( undefined ),
		};
		fetch.mockResolvedValueOnce( stale ).mockResolvedValueOnce( stale );
		const send = createAdminRestClient( request, 'https://example.com' );

		await expect( send( 'wp/v2/pages' ) ).resolves.toBe( stale );
		expect( get ).toHaveBeenCalledTimes( 2 );

		const file = createReadStream( __filename );
		try {
			await expect(
				send( 'wp/v2/media', { method: 'POST', multipart: { file } } )
			).resolves.toBe( restResponse );
			expect( fetch ).toHaveBeenLastCalledWith(
				'https://example.com/wp-json/wp/v2/media',
				expect.objectContaining( {
					headers: { 'X-WP-Nonce': 'cccccccccc' },
				} )
			);
			expect( get ).toHaveBeenCalledTimes( 3 );
		} finally {
			file.destroy();
		}
	} );

	it.each( [
		'https://other.example/orders',
		'../wp-admin',
		'%2e%2e/wp-admin',
	] )(
		'rejects a route outside the WordPress REST API: %s',
		async ( route ) => {
			const { request, get, fetch } = mockAdminRequest();
			const send = createAdminRestClient(
				request,
				'https://example.com'
			);

			await expect( send( route ) ).rejects.toThrow(
				'within the WordPress REST API'
			);
			expect( get ).not.toHaveBeenCalled();
			expect( fetch ).not.toHaveBeenCalled();
		}
	);
} );
