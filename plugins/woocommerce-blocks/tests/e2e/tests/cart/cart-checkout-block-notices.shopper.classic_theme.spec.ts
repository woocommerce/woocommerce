/**
 * External dependencies
 */
import { expect, test as base } from '@woocommerce/e2e-playwright-utils';
import {
	cli,
	CLASSIC_THEME_SLUG,
	CLASSIC_CHILD_THEME_WITH_CLASSIC_NOTICES_TEMPLATE_SLUG,
	CLASSIC_CHILD_THEME_WITH_BLOCK_NOTICES_FILTER_SLUG,
	CLASSIC_CHILD_THEME_WITH_BLOCK_NOTICES_TEMPLATE_SLUG,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { CheckoutPage } from '../checkout/checkout.page';
import { REGULAR_PRICED_PRODUCT_NAME } from '../checkout/constants';

const test = base.extend< { checkoutPageObject: CheckoutPage } >( {
	checkoutPageObject: async ( { page }, use ) => {
		const pageObject = new CheckoutPage( {
			page,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Shopper → Notice Templates', () => {
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

	test( 'default classic notice templates are visible', async ( {
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

		// We're explicitly checking the CSS classes of the classic notices.
		await expect(
			page.locator( '.woocommerce-notices-wrapper .woocommerce-message' )
		).toBeVisible();

		await page.reload();
		await page.getByPlaceholder( 'Coupon code' ).fill( 'testcoupon' );
		await page.getByRole( 'button', { name: 'Apply coupon' } ).click();

		await expect(
			page.getByText( 'Coupon code already applied!', { exact: true } )
		).toBeVisible();

		// We're explicitly checking the CSS classes of the classic notices.
		await expect(
			page.locator( '.woocommerce-notices-wrapper .woocommerce-error' )
		).toBeVisible();

		await page.getByLabel( 'Remove Polo from cart' ).click();

		await expect(
			page.getByText( 'Your cart is currently empty.', { exact: true } )
		).toBeVisible();

		// We're explicitly checking the CSS classes of the classic notices.
		await expect(
			page.locator( '.woocommerce-notices-wrapper .woocommerce-info' )
		).toBeVisible();
	} );

	test( 'custom classic notice templates are visible by template overwrite', async ( {
		frontendUtils,
		page,
	} ) => {
		await cli(
			`npm run wp-env run tests-cli -- wp theme activate ${ CLASSIC_CHILD_THEME_WITH_CLASSIC_NOTICES_TEMPLATE_SLUG }`
		);

		await frontendUtils.goToCartShortcode();
		await page.getByPlaceholder( 'Coupon code' ).fill( 'testcoupon' );
		await page.getByRole( 'button', { name: 'Apply coupon' } ).click();

		await expect(
			page.getByText(
				'CLASSIC SUCCESS NOTICE: Coupon code applied successfully.'
			)
		).toBeVisible();

		// We're explicitly checking the CSS classes of the classic notices.
		await expect(
			page.locator( '.woocommerce-notices-wrapper .woocommerce-message' )
		).toBeVisible();

		await page.reload();
		await page.getByPlaceholder( 'Coupon code' ).fill( 'testcoupon' );
		await page.getByRole( 'button', { name: 'Apply coupon' } ).click();

		await expect(
			page.getByText(
				'CLASSIC ERROR NOTICE: Coupon code already applied!'
			)
		).toBeVisible();

		// We're explicitly checking the CSS classes of the classic notices.
		await expect(
			page.locator( '.woocommerce-notices-wrapper .woocommerce-error' )
		).toBeVisible();

		await page.getByLabel( 'Remove Polo from cart' ).click();

		await expect(
			page.getByText(
				'CLASSIC INFO NOTICE: Your cart is currently empty.'
			)
		).toBeVisible();

		// We're explicitly checking the CSS classes of the classic notices.
		await expect(
			page.locator( '.woocommerce-notices-wrapper .woocommerce-info' )
		).toBeVisible();

		await cli(
			`npm run wp-env run tests-cli -- wp theme activate ${ CLASSIC_THEME_SLUG }`
		);
	} );

	test( 'custom block notice templates are visible by template overwrite', async ( {
		frontendUtils,
		page,
	} ) => {
		await cli(
			`npm run wp-env run tests-cli -- wp theme activate ${ CLASSIC_CHILD_THEME_WITH_BLOCK_NOTICES_TEMPLATE_SLUG }`
		);

		await frontendUtils.goToCartShortcode();
		await page.getByPlaceholder( 'Coupon code' ).fill( 'testcoupon' );
		await page.getByRole( 'button', { name: 'Apply coupon' } ).click();

		await expect(
			page.getByText(
				'BLOCK SUCCESS NOTICE: Coupon code applied successfully.'
			)
		).toBeVisible();

		// We're explicitly checking the CSS classes of the block notices, and that the SVG is visible.
		await expect(
			page.locator( '.wc-block-components-notice-banner.is-success svg' )
		).toBeVisible();

		await page.reload();
		await page.getByPlaceholder( 'Coupon code' ).fill( 'testcoupon' );
		await page.getByRole( 'button', { name: 'Apply coupon' } ).click();

		await expect(
			page.getByText( 'BLOCK ERROR NOTICE: Coupon code already applied!' )
		).toBeVisible();

		// We're explicitly checking the CSS classes of the block notices, and that the SVG is visible.
		await expect(
			page.locator( '.wc-block-components-notice-banner.is-error svg' )
		).toBeVisible();

		await page.getByLabel( 'Remove Polo from cart' ).click();

		await expect(
			page.getByText( 'BLOCK INFO NOTICE: Your cart is currently empty.' )
		).toBeVisible();

		// We're explicitly checking the CSS classes of the block notices, and that the SVG is visible.
		await expect(
			page.locator( '.wc-block-components-notice-banner.is-success svg' )
		).toBeVisible();

		await cli(
			`npm run wp-env run tests-cli -- wp theme activate ${ CLASSIC_THEME_SLUG }`
		);
	} );

	test( 'default block notice templates are visible by filter', async ( {
		frontendUtils,
		page,
	} ) => {
		await cli(
			`npm run wp-env run tests-cli -- wp theme activate ${ CLASSIC_CHILD_THEME_WITH_BLOCK_NOTICES_FILTER_SLUG }`
		);

		await frontendUtils.goToCartShortcode();
		await page.getByPlaceholder( 'Coupon code' ).fill( 'testcoupon' );
		await page.getByRole( 'button', { name: 'Apply coupon' } ).click();

		await expect(
			page.getByText( 'Coupon code applied successfully.' )
		).toBeVisible();

		// We're explicitly checking the CSS classes and that the SVG is visible.
		await expect(
			page.locator( '.wc-block-components-notice-banner.is-success svg' )
		).toBeVisible();

		await page.reload();
		await page.getByPlaceholder( 'Coupon code' ).fill( 'testcoupon' );
		await page.getByRole( 'button', { name: 'Apply coupon' } ).click();

		await expect(
			page.getByText( 'Coupon code already applied!' )
		).toBeVisible();

		// We're explicitly checking the CSS classes and that the SVG is visible.
		await expect(
			page.locator( '.wc-block-components-notice-banner.is-error svg' )
		).toBeVisible();

		await page.getByLabel( 'Remove Polo from cart' ).click();

		await expect(
			page.getByText( 'Your cart is currently empty.' )
		).toBeVisible();

		// We're explicitly checking the CSS classes and that the SVG is visible.
		await expect(
			page.locator( '.wc-block-components-notice-banner.is-success svg' )
		).toBeVisible();

		await cli(
			`npm run wp-env run tests-cli -- wp theme activate ${ CLASSIC_THEME_SLUG }`
		);
	} );
} );
