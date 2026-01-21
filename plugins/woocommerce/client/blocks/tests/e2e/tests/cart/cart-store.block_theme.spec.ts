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

	test( 'should add product to cart with expired nonce (simulated cached page)', async ( {
		page,
		frontendUtils,
	} ) => {
		// 1. Visit the shop page and capture the HTML (simulating caching).
		await frontendUtils.goToShop();
		const cachedHtml = await page.content();

		// 2. Wait for the nonce to expire (It takes half the time).
		await page.waitForTimeout( 2500 );

		// 3. Set up route interception to serve the "cached" shop page.
		await page.route( '**/shop**', async ( route ) => {
			// Only intercept document requests, not assets.
			if ( route.request().resourceType() === 'document' ) {
				await route.fulfill( {
					status: 200,
					contentType: 'text/html',
					body: cachedHtml,
				} );
			} else {
				await route.continue();
			}
		} );

		// 4. Navigate to the "cached" shop page with expired nonce.
		await frontendUtils.goToShop();

		// 5. Add to cart - this should work after the nonce refresh fix.
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );

		// 6. Verify product was added successfully.
		await frontendUtils.goToCheckout();
		await expect(
			page.getByText( REGULAR_PRICED_PRODUCT_NAME, { exact: true } )
		).toBeVisible();
	} );
} );
