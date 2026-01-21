/**
 * External dependencies
 */
import { test, expect, guestFile } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { REGULAR_PRICED_PRODUCT_NAME } from '../checkout/constants';

test.describe( 'Cart Store Nonce Handling', () => {
	test.use( { storageState: guestFile } );

	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-stale-nonce-in-cart-store'
		);
	} );

	test( 'should add product to cart with stale nonce (simulated cached page)', async ( {
		page,
		frontendUtils,
	} ) => {
		await frontendUtils.goToShop();

		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );

		// Verify product was added successfully.
		await frontendUtils.goToCheckout();
		await expect(
			page.getByText( REGULAR_PRICED_PRODUCT_NAME, { exact: true } )
		).toBeVisible();
	} );

	test( 'should add product to cart before and after page refresh with stale nonce', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();

		// Add product before refresh.
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );

		// Verify product was added successfully.
		await frontendUtils.goToCheckout();
		await expect(
			page.getByText( REGULAR_PRICED_PRODUCT_NAME, { exact: true } )
		).toBeVisible();

		// Go back to shop and refresh to simulate returning to a cached page.
		await frontendUtils.goToShop();
		await page.reload();

		// Add product after refresh - should still work.
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );

		// Verify quantity is now 2.
		await miniCartUtils.openMiniCart();
		await expect(
			page.getByLabel( /Quantity of .* in your cart/ )
		).toHaveValue( '2' );
	} );
} );
