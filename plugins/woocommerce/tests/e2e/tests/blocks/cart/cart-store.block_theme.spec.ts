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

	test( 'should refresh nonce from Store API and use it for cart mutations', async ( {
		page,
		frontendUtils,
	} ) => {
		const refreshCartResponse = page.waitForResponse(
			( response ) =>
				response.url().includes( '/wc/store/v1/cart' ) &&
				response.request().method() === 'GET'
		);
		let initialBatchRequestNonce: string | null = null;
		let invalidateNextBatch = false;
		let invalidBatchRequestNonce: string | null = null;
		let invalidResponseNonce: string | null = null;
		let invalidResponseBody = '';
		let captureRecoveryBatch = false;
		let recoveryBatchRequestNonce: string | null = null;

		// Forward exactly one invalid nonce to the real Store API so its response
		// supplies the replacement nonce the client must use for the next mutation.
		await page.route( '**/wc/store/v1/batch**', async ( route ) => {
			const batch = JSON.parse( route.request().postData() || '{}' );
			const [ batchRequest ] = batch.requests || [];
			const isCartAddItemBatch =
				batch.requests?.length === 1 &&
				batchRequest?.method === 'POST' &&
				batchRequest.path === '/wc/store/v1/cart/add-item';
			const requestNonce = isCartAddItemBatch
				? batchRequest.headers?.Nonce || null
				: null;

			if ( invalidateNextBatch && isCartAddItemBatch ) {
				invalidateNextBatch = false;
				invalidBatchRequestNonce = requestNonce;
				const invalidBatch = {
					...batch,
					requests: [
						{
							...batchRequest,
							headers: {
								...batchRequest.headers,
								Nonce: 'invalid-test-nonce',
							},
						},
					],
				};
				const response = await route.fetch( {
					postData: JSON.stringify( invalidBatch ),
				} );
				invalidResponseNonce = response.headers().nonce || null;
				invalidResponseBody = await response.text();
				await route.fulfill( { response } );
				return;
			}

			if (
				isCartAddItemBatch &&
				captureRecoveryBatch &&
				! recoveryBatchRequestNonce
			) {
				recoveryBatchRequestNonce = requestNonce;
			} else if ( isCartAddItemBatch && ! initialBatchRequestNonce ) {
				initialBatchRequestNonce = requestNonce;
			}

			const response = await route.fetch();
			await route.fulfill( { response } );
		} );

		await frontendUtils.goToShop();
		const refreshCartNonce =
			( await refreshCartResponse ).headers().nonce || null;
		expect( refreshCartNonce ).toBeTruthy();

		// Adding a product should use the nonce from refreshCartItems.
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		expect( initialBatchRequestNonce ).toBe( refreshCartNonce );

		// Forward an invalid nonce and let the real Store API return its error and replacement nonce.
		invalidateNextBatch = true;
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		expect( invalidResponseBody ).toContain(
			'woocommerce_rest_invalid_nonce'
		);
		await expect( page.getByText( 'Nonce is invalid.' ) ).toBeVisible();
		expect( invalidBatchRequestNonce ).toBe( initialBatchRequestNonce );
		expect( invalidResponseNonce ).toBeTruthy();

		// The next mutation should use the exact nonce returned by the invalid response.
		captureRecoveryBatch = true;
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		expect( recoveryBatchRequestNonce ).toBe( invalidResponseNonce );

		// Verify the product was actually added to the cart properly.
		await frontendUtils.goToCart();
		await expect(
			page.getByLabel(
				`Quantity of ${ REGULAR_PRICED_PRODUCT_NAME } in your cart.`
			)
		).toHaveValue( '2' );
	} );
} );
