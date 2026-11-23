/**
 * External dependencies
 */
import { expect, test, guestFile } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import {
	REGULAR_PRICED_PRODUCT_NAME,
	INVALID_COUPON,
} from '../checkout/constants';

test.describe( 'Shopper → Notice Templates', () => {
	// A guest cart, because all workers share the admin cart.
	test.use( { storageState: guestFile } );

	test.beforeEach( async ( { frontendUtils } ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
	} );

	test( 'default block notice templates, except for coupon errors, are visible', async ( {
		frontendUtils,
		page,
	} ) => {
		await frontendUtils.goToCartShortcode();
		await page.getByPlaceholder( 'Coupon code' ).fill( 'testcoupon' );
		await page.getByRole( 'button', { name: 'Apply coupon' } ).click();

		await expect(
			page.getByText( 'Coupon code applied successfully.', {
				exact: true,
			} )
		).toBeVisible();

		// We're explicitly checking the CSS classes of the block notices, and that the SVG is visible.
		await expect(
			page.locator( '.wc-block-components-notice-banner.is-success svg' )
		).toBeVisible();

		await page.reload();
		await page.getByPlaceholder( 'Coupon code' ).fill( 'testcoupon' );
		await page.getByRole( 'button', { name: 'Apply coupon' } ).click();

		await expect(
			page.getByText( 'Coupon code "testcoupon" already applied!', {
				exact: true,
			} )
		).toBeVisible();

		// We're explicitly checking the CSS classes of the block notices, and that the SVG is hidden.
		await expect(
			page.locator( '.wc-block-components-notice-banner.is-error svg' )
		).toBeHidden();

		await page.getByLabel( 'Remove Polo from cart' ).click();

		await expect(
			page.getByText( 'Your cart is currently empty.', {
				exact: true,
			} )
		).toBeVisible();

		// We're explicitly checking the CSS classes of the block notices, and that the SVG is visible.
		await expect(
			page.locator( '.wc-block-components-notice-banner.is-success svg' )
		).toBeVisible();
	} );

	test( 'coupon inline notice is visible', async ( {
		frontendUtils,
		page,
	} ) => {
		await frontendUtils.goToCartShortcode();
		await page.getByPlaceholder( 'Coupon code' ).fill( INVALID_COUPON );
		await page.getByRole( 'button', { name: 'Apply coupon' } ).click();

		await expect(
			page.getByText(
				`Coupon "${ INVALID_COUPON }" cannot be applied because it does not exist.`,
				{
					exact: true,
				}
			)
		).toBeVisible();

		// We're explicitly checking the CSS classes of the block notices, and that the SVG is hidden.
		await expect(
			page.locator( '.wc-block-components-notice-banner.is-error svg' )
		).toBeHidden();

		await expect( page.locator( '.coupon-error-notice' ) ).toBeVisible();
	} );
} );
