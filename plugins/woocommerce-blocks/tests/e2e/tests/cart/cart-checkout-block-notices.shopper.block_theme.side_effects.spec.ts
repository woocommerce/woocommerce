/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-playwright-utils';
import {
	cli,
	BLOCK_THEME_SLUG,
	BLOCK_CHILD_THEME_SLUG,
} from '@woocommerce/e2e-utils';
/**
 * Internal dependencies
 */
import { REGULAR_PRICED_PRODUCT_NAME } from '../checkout/constants';

test.describe( 'Shopper → Block Notice Templates', () => {
	test.beforeEach( async ( { wpCliUtils, frontendUtils } ) => {
		const cartShortcodeID = await wpCliUtils.getPostIDByTitle(
			'Cart Shortcode'
		);
		await cli(
			`npm run wp-env run tests-cli -- wp option update woocommerce_cart_page_id ${ cartShortcodeID }`
		);

		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
	} );

	test.afterEach( async ( { wpCliUtils, frontendUtils } ) => {
		const cartID = await wpCliUtils.getPostIDByTitle( 'Cart Shortcode' );
		await cli(
			`npm run wp-env run tests-cli -- wp option update woocommerce_cart_page_id ${ cartID }`
		);

		await frontendUtils.emptyCart();
	} );

	test( 'default templates are visible', async ( {
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

		// We're explicitly checking for the following CSS classes here:
		// .wc-block-components-notice-banner.is-success
		await expect(
			page.locator( '.wc-block-components-notice-banner.is-success' )
		).toBeVisible();

		await page.reload();
		await page.getByPlaceholder( 'Coupon code' ).fill( 'testcoupon' );
		await page.getByRole( 'button', { name: 'Apply coupon' } ).click();

		await expect(
			page.getByText( 'Coupon code already applied!', {
				exact: true,
			} )
		).toBeVisible();

		// We're explicitly checking for the following CSS classes here:
		// .wc-block-components-notice-banner.is-error
		await expect(
			page.locator( '.wc-block-components-notice-banner.is-error' )
		).toBeVisible();

		await page.getByLabel( 'Remove Polo from cart' ).click();

		await expect(
			page.getByText( 'Your cart is currently empty.', {
				exact: true,
			} )
		).toBeVisible();

		// We're explicitly checking for the following CSS classes here:
		// .wc-block-components-notice-banner.is-success
		await expect(
			page.locator( '.wc-block-components-notice-banner.is-success' )
		).toBeVisible();
	} );

	test( 'custom templates are visible', async ( { frontendUtils, page } ) => {
		await cli(
			`npm run wp-env run tests-cli -- wp theme activate ${ BLOCK_CHILD_THEME_SLUG }`
		);

		await frontendUtils.goToCartShortcode();
		await page.getByPlaceholder( 'Coupon code' ).fill( 'testcoupon' );
		await page.getByRole( 'button', { name: 'Apply coupon' } ).click();

		await expect(
			page.getByText(
				'BLOCK SUCCESS NOTICE - Coupon code applied successfully.'
			)
		).toBeVisible();

		// We're explicitly checking for the following CSS classes here:
		// .wc-block-components-notice-banner.is-success
		await expect(
			page.locator( '.wc-block-components-notice-banner.is-success' )
		).toBeVisible();

		await page.reload();
		await page.getByPlaceholder( 'Coupon code' ).fill( 'testcoupon' );
		await page.getByRole( 'button', { name: 'Apply coupon' } ).click();

		await expect(
			page.getByText(
				'BLOCK ERROR NOTICE - Coupon code already applied!'
			)
		).toBeVisible();

		// We're explicitly checking for the following CSS classes here:
		// .wc-block-components-notice-banner.is-error
		await expect(
			page.locator( '.wc-block-components-notice-banner.is-error' )
		).toBeVisible();

		await page.getByLabel( 'Remove Polo from cart' ).click();

		await expect(
			page.getByText(
				'BLOCK INFO NOTICE – Your cart is currently empty.'
			)
		).toBeVisible();

		// We're explicitly checking for the following CSS classes here:
		// .wc-block-components-notice-banner.is-success
		await expect(
			page.locator( '.wc-block-components-notice-banner.is-success' )
		).toBeVisible();

		await cli(
			`npm run wp-env run tests-cli -- wp theme activate ${ BLOCK_THEME_SLUG }`
		);
	} );
} );
