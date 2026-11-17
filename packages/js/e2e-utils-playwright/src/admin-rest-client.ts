/**
 * External dependencies
 */
import type { APIRequestContext } from '@playwright/test';

type AdminRestRequestOptions = Omit<
	NonNullable< Parameters< APIRequestContext[ 'fetch' ] >[ 1 ] >,
	'headers' | 'maxRedirects'
>;

/**
 * Create a WooCommerce REST client for an authenticated WordPress admin session.
 * Create one client per test session. The client reuses its REST nonce and returns
 * Playwright responses for the caller to check.
 *
 * @param request Playwright request context with the admin session cookies.
 * @param siteUrl WordPress site URL, including any installation subdirectory.
 * @return A function that accepts a route relative to wp-json/wc/v3 and request options.
 */
export function createAdminRestClient(
	request: APIRequestContext,
	siteUrl: string
) {
	const base = new URL( siteUrl );
	if ( ! base.pathname.endsWith( '/' ) ) {
		base.pathname += '/';
	}
	const restUrl = new URL( 'wp-json/wc/v3/', base );
	const nonceUrl = new URL(
		'wp-admin/admin-ajax.php?action=rest-nonce',
		base
	);
	let nonce: Promise< string > | undefined;

	async function getNonce() {
		const response = await request.get( nonceUrl.href, {
			maxRedirects: 0,
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

	return async ( route: string, options?: AdminRestRequestOptions ) => {
		const endpoint = new URL( route.replace( /^\//, '' ), restUrl );
		if (
			endpoint.origin !== restUrl.origin ||
			! endpoint.pathname.startsWith( restUrl.pathname )
		) {
			throw new Error(
				'Expected a route within the WooCommerce REST API.'
			);
		}

		nonce ??= getNonce().catch( ( error ) => {
			nonce = undefined;
			throw error;
		} );

		return request.fetch( endpoint.href, {
			...options,
			headers: { 'X-WP-Nonce': await nonce },
			maxRedirects: 0,
		} );
	};
}
