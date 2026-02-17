/**
 * External dependencies
 */
import { test, expect, guestFile } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { REGULAR_PRICED_PRODUCT_NAME } from '../checkout/constants';

test.describe( 'Cart Store', () => {
	test.use( { storageState: guestFile } );

	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-short-nonce-life'
		);
	} );

	test( 'should refresh nonce from Store API and use it for cart mutations', async ( {
		page,
		frontendUtils,
	} ) => {
		let refreshNonce: string | null = null;
		let requestNonce: string | null = null;
		let responseNonce: string | null = null;

		// Intercept GET /cart (refreshCartItems) to capture the nonce.
		await page.route( '**/wc/store/v1/cart', async ( route ) => {
			if ( route.request().method() === 'GET' ) {
				const response = await route.fetch();
				refreshNonce = response.headers()[ 'nonce' ] || null;
				await route.fulfill( { response } );
			} else {
				await route.continue();
			}
		} );

		// Intercept batch requests to track which nonce the client sends
		// and which nonce the server returns.
		await page.route( '**/wc/store/v1/batch**', async ( route ) => {
			requestNonce = route.request().headers()[ 'nonce' ] || null;
			const response = await route.fetch();
			responseNonce = response.headers()[ 'nonce' ] || null;
			await route.fulfill( { response } );
		} );

		await frontendUtils.goToShop();

		// 1. refreshCartItems should return a nonce.
		expect( refreshNonce ).toBeTruthy();

		// 2. Adding a product should use the nonce from refreshCartItems.
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		expect( requestNonce ).toBe( refreshNonce );

		// 3. Adding another product should use the second nonce.
		const previousResponseNonce = responseNonce;
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		expect( requestNonce ).not.toBe( refreshNonce );
		expect( requestNonce ).toBe( previousResponseNonce );
	} );
} );
