/**
 * External dependencies
 */
import type { APIResponse, Route } from '@playwright/test';
import { test, expect, guestFile } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { REGULAR_PRICED_PRODUCT_NAME } from '../checkout/constants';

test.describe( 'Cart Store', () => {
	test.use( { storageState: guestFile } );

	test( 'should refresh nonce from Store API and use it for cart mutations', async ( {
		page,
		frontendUtils,
	} ) => {
		let refreshNonce: string | null = null;
		let requestNonce: string | null = null;
		let responseNonce: string | null = null;
		let batchCount = 0;
		// Between the first and second mutation, every response hands the
		// client a stale nonce, so the second mutation is rejected without
		// depending on the nonce lifetime.
		const STALE_NONCE = 'stale-nonce';
		const fulfillWithNonce = async (
			route: Route,
			response: APIResponse
		) => {
			await route.fulfill(
				batchCount === 1
					? {
							response,
							headers: {
								...response.headers(),
								nonce: STALE_NONCE,
							},
						}
					: { response }
			);
		};

		// Intercept GET /cart (refreshCartItems) to capture the nonce.
		await page.route( '**/wc/store/v1/cart**', async ( route ) => {
			if ( route.request().method() === 'GET' ) {
				const response = await route.fetch();
				refreshNonce ??= response.headers().nonce || null;
				await fulfillWithNonce( route, response );
			} else {
				await route.continue();
			}
		} );

		// Intercept batch requests to track which nonce the client sends
		// and which nonce the server returns.
		await page.route( '**/wc/store/v1/batch**', async ( route ) => {
			batchCount++;
			requestNonce = route.request().headers().nonce || null;
			const response = await route.fetch();
			responseNonce = response.headers().nonce || null;
			await fulfillWithNonce( route, response );
		} );

		await frontendUtils.goToShop();

		// The GET /cart fires during page load, so a waitForResponse set up
		// here can miss it — poll the intercepted nonce instead.
		await expect.poll( () => refreshNonce ).toBeTruthy();

		// Adding a product should use the nonce from refreshCartItems.
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		expect( requestNonce ).toBe( refreshNonce );

		// Adding another product should fail because it is using a stale nonce.
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		expect( requestNonce ).toBe( STALE_NONCE );
		await expect( page.getByText( 'Nonce is invalid.' ) ).toBeVisible();
		const previousResponseNonce = responseNonce;

		// The client should use the nonce from the failed response and succeed.
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		expect( requestNonce ).toBe( previousResponseNonce );

		// Verify the product was actually added to the cart properly.
		await frontendUtils.goToCart();
		await expect(
			page.getByLabel(
				`Quantity of ${ REGULAR_PRICED_PRODUCT_NAME } in your cart.`
			)
		).toHaveValue( '2' );
	} );
} );
