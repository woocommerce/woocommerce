/**
 * External dependencies
 */
import type { APIRequestContext } from '@playwright/test';
import { Readable } from 'node:stream';

type AdminRestRequestOptions = Omit<
	NonNullable< Parameters< APIRequestContext[ 'fetch' ] >[ 1 ] >,
	'failOnStatusCode' | 'maxRedirects'
>;

/**
 * Create a WordPress REST client for an authenticated Playwright admin session.
 * It reuses a nonce and retries once if WordPress rejects it. For an upload
 * using a stream, the caller must supply a fresh stream and retry.
 *
 * @param request Playwright request context with the admin session cookies.
 * @param siteUrl WordPress site URL, including any installation subdirectory.
 * @return A function that accepts a route relative to wp-json and returns an APIResponse.
 */
export function createAdminRestClient(
	request: APIRequestContext,
	siteUrl: string
) {
	const base = new URL( siteUrl );
	if ( ! base.pathname.endsWith( '/' ) ) {
		base.pathname += '/';
	}
	const restUrl = new URL( 'wp-json/', base );
	const nonceUrl = new URL(
		'wp-admin/admin-ajax.php?action=rest-nonce',
		base
	);
	let nonce: Promise< string > | undefined;

	async function getNonce() {
		const response = await request.get( nonceUrl.href, {
			maxRedirects: 0,
			failOnStatusCode: false,
		} );
		try {
			const value = ( await response.text() ).trim();
			if ( ! response.ok() || ! /^[a-f0-9]{10}$/i.test( value ) ) {
				throw new Error(
					`Could not get an admin REST nonce (HTTP ${ response.status() }).`
				);
			}
			return value;
		} finally {
			await response.dispose();
		}
	}

	function getCachedNonce() {
		nonce ??= getNonce().catch( ( error ) => {
			nonce = undefined;
			throw error;
		} );
		return nonce;
	}

	return async ( route: string, options?: AdminRestRequestOptions ) => {
		const endpoint = new URL( route.replace( /^\//, '' ), restUrl );
		if (
			endpoint.origin !== restUrl.origin ||
			! endpoint.pathname.startsWith( restUrl.pathname )
		) {
			throw new Error(
				'Expected a route within the WordPress REST API.'
			);
		}

		const headers = Object.fromEntries(
			Object.entries( options?.headers ?? {} ).filter(
				( [ name ] ) => name.toLowerCase() !== 'x-wp-nonce'
			)
		);
		const send = ( value: string ) =>
			request.fetch( endpoint.href, {
				...options,
				headers: { ...headers, 'X-WP-Nonce': value },
				failOnStatusCode: false,
				maxRedirects: 0,
			} );
		const usedNonce = getCachedNonce();
		const response = await send( await usedNonce );
		if (
			response.status() !== 403 ||
			( await response.json().catch( () => null ) )?.code !==
				'rest_cookie_invalid_nonce'
		) {
			return response;
		}

		if ( nonce === usedNonce ) {
			nonce = undefined;
		}
		// Playwright consumes multipart upload streams on the first request.
		if (
			Object.values( options?.multipart ?? {} ).some(
				( value ) => value instanceof Readable
			)
		) {
			return response;
		}

		await response.dispose();
		return send( await getCachedNonce() );
	};
}
