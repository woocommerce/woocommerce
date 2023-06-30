/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import { cli } from '@woocommerce/e2e-utils';

test.describe(
	'Tests permalink settings for the cart and checkout templates',
	async () => {
		test.afterAll( async () => {
			await cli(
				'npm run wp-env run tests-cli "wp option update woocommerce_cart_page_endpoint cart"'
			);
			await cli(
				'npm run wp-env run tests-cli "wp option update woocommerce_checkout_page_endpoint checkout"'
			);
		} );

		test.describe( 'Settings page', () => {
			test( 'Load advanced settings', async ( { page } ) => {
				await page.goto(
					'/wp-admin/admin.php?page=wc-settings&tab=advanced'
				);
				const title = page
					.locator( 'div.wrap.woocommerce > form > h2' )
					.first();
				await expect( title ).toHaveText( 'Page setup' );
			} );
			test( 'Permlink settings exist', async ( { page } ) => {
				await page.goto(
					'/wp-admin/admin.php?page=wc-settings&tab=advanced'
				);
				const cartInput = page.getByLabel( 'Cart page', {
					exact: true,
				} );
				const checkoutInput = page.getByLabel( 'Checkout page', {
					exact: true,
				} );

				await expect( cartInput ).toBeVisible();
				await expect( checkoutInput ).toBeVisible();
			} );
		} );

		test.describe( 'Frontend templates are updated', () => {
			test.beforeEach( async ( { frontendUtils } ) => {
				await frontendUtils.goToShop();
				await frontendUtils.addToCart();
			} );

			test( 'Changing cart permalink works', async ( { page } ) => {
				await page.goto(
					'/wp-admin/admin.php?page=wc-settings&tab=advanced'
				);
				const cartInput = page.getByLabel( 'Cart page', {
					exact: true,
				} );
				cartInput.fill( 'updated-cart-permalink' );
				await page.click( 'button[name="save"]' );
				await page.waitForLoadState( 'networkidle' );

				// Visit the updated page.
				await page.goto( '/updated-cart-permalink', {
					waitUntil: 'networkidle',
				} );
				const cartText = await page.getByText( 'Proceed to checkout' );
				expect( cartText ).toBeVisible();
			} );

			test( 'Changing checkout permalink works', async ( { page } ) => {
				await page.goto(
					'/wp-admin/admin.php?page=wc-settings&tab=advanced'
				);
				const checkoutInput = page.getByLabel( 'Checkout page', {
					exact: true,
				} );
				checkoutInput.fill( 'updated-checkout-permalink' );
				await page.click( 'button[name="save"]' );
				await page.waitForLoadState( 'networkidle' );

				// Visit the updated page.
				await page.goto( '/updated-checkout-permalink', {
					waitUntil: 'networkidle',
				} );
				const cartText = await page.getByText( 'Place Order' );
				expect( cartText ).toBeVisible();
			} );
		} );
	}
);
