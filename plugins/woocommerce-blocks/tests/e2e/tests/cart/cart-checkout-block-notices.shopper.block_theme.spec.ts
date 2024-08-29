/**
 * External dependencies
 */
import {
	expect,
	test as base,
	wpCLI,
	BLOCK_THEME_SLUG,
	BLOCK_CHILD_THEME_WITH_BLOCK_NOTICES_FILTER_SLUG,
	BLOCK_CHILD_THEME_WITH_BLOCK_NOTICES_TEMPLATE_SLUG,
	BLOCK_CHILD_THEME_WITH_CLASSIC_NOTICES_TEMPLATE_SLUG,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { CheckoutPage } from '../checkout/checkout.page';
import {
	REGULAR_PRICED_PRODUCT_NAME,
	INVALID_COUPON,
} from '../checkout/constants';

const test = base.extend< { checkoutPageObject: CheckoutPage } >( {
	checkoutPageObject: async ( { page }, use ) => {
		const pageObject = new CheckoutPage( {
			page,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Shopper → Notice Templates', () => {
	test.beforeEach( async ( { frontendUtils } ) => {
		const cliOutput = await wpCLI(
			'post list --title="Cart Shortcode" --post_type=page --field=ID'
		);
		const cartShortcodeID = cliOutput.stdout.match( /\d+/g )?.pop();

		await wpCLI(
			`option update woocommerce_cart_page_id ${ cartShortcodeID }`
		);

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
			page.getByText( 'Coupon code already applied!', {
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

	test( 'custom block notice templates, except for coupon errors, are visible by template overwrite', async ( {
		requestUtils,
		frontendUtils,
		page,
	} ) => {
		await requestUtils.activateTheme(
			BLOCK_CHILD_THEME_WITH_BLOCK_NOTICES_TEMPLATE_SLUG
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

		// We're explicitly checking the CSS classes of the block notices, and that the SVG is hidden.
		await expect(
			page.locator( '.wc-block-components-notice-banner.is-error svg' )
		).toBeHidden();

		await page.getByLabel( 'Remove Polo from cart' ).click();

		await expect(
			page.getByText( 'BLOCK INFO NOTICE: Your cart is currently empty.' )
		).toBeVisible();

		// We're explicitly checking the CSS classes of the block notices, and that the SVG is visible.
		await expect(
			page.locator( '.wc-block-components-notice-banner.is-success svg' )
		).toBeVisible();

		await requestUtils.activateTheme( BLOCK_THEME_SLUG );
	} );

	test( 'classic notice templates, except for coupon errors, are visible by template overwrite', async ( {
		requestUtils,
		frontendUtils,
		page,
	} ) => {
		await requestUtils.activateTheme(
			BLOCK_CHILD_THEME_WITH_CLASSIC_NOTICES_TEMPLATE_SLUG
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
		).toBeHidden();

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

		await requestUtils.activateTheme( BLOCK_THEME_SLUG );
	} );

	test( 'default classic notice templates cannot be triggered by filter', async ( {
		requestUtils,
		frontendUtils,
		page,
	} ) => {
		await requestUtils.activateTheme(
			BLOCK_CHILD_THEME_WITH_BLOCK_NOTICES_FILTER_SLUG
		);

		await frontendUtils.goToCartShortcode();
		await page.getByPlaceholder( 'Coupon code' ).fill( 'testcoupon' );
		await page.getByRole( 'button', { name: 'Apply coupon' } ).click();

		await expect(
			page.getByText( 'Coupon code applied successfully.', {
				exact: true,
			} )
		).toBeVisible();

		// We're explicitly checking the CSS classes and that the SVG is visible.
		await expect(
			page.locator( '.wc-block-components-notice-banner.is-success svg' )
		).toBeVisible();

		await page.reload();
		await page.getByPlaceholder( 'Coupon code' ).fill( 'testcoupon' );
		await page.getByRole( 'button', { name: 'Apply coupon' } ).click();

		await expect(
			page.getByText( 'Coupon code already applied!', {
				exact: true,
			} )
		).toBeVisible();

		// We're explicitly checking the CSS classes and that the SVG is hidden.
		await expect(
			page.locator( '.wc-block-components-notice-banner.is-error svg' )
		).toBeHidden();

		await page.getByLabel( 'Remove Polo from cart' ).click();

		await expect(
			page.getByText( 'Your cart is currently empty.', {
				exact: true,
			} )
		).toBeVisible();

		// We're explicitly checking the CSS classes and that the SVG is visible.
		await expect(
			page.locator( '.wc-block-components-notice-banner.is-success svg' )
		).toBeVisible();

		await requestUtils.activateTheme( BLOCK_THEME_SLUG );
	} );

	test( 'coupon inline notice is visible', async ( {
		frontendUtils,
		page,
	} ) => {
		await frontendUtils.goToCartShortcode();
		await page.getByPlaceholder( 'Coupon code' ).fill( INVALID_COUPON );
		await page.getByRole( 'button', { name: 'Apply coupon' } ).click();

		await expect(
			page.getByText( `Coupon "${ INVALID_COUPON }" does not exist!`, {
				exact: true,
			} )
		).toBeVisible();

		// We're explicitly checking the CSS classes of the block notices, and that the SVG is hidden.
		await expect(
			page.locator( '.wc-block-components-notice-banner.is-error svg' )
		).toBeHidden();

		await expect( page.locator( '.coupon-error-notice' ) ).toBeVisible();
	} );
} );
