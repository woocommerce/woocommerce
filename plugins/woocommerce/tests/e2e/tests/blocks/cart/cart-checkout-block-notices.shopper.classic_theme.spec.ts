/**
 * External dependencies
 */
import {
	expect,
	test as base,
	wpCLI,
	CLASSIC_THEME_SLUG,
	CLASSIC_CHILD_THEME_WITH_BLOCK_NOTICES_FILTER_SLUG,
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

const setProductOutOfStock = async ( productName: string ) => {
	const productIdOutput = await wpCLI(
		`post list --title="${ productName }" --post_type=product --field=ID`
	);
	// npm writes its own banner to stdout, so the ID is the line that holds
	// nothing but digits.
	const productId = productIdOutput.stdout.match( /^\d+$/m )?.[ 0 ];
	if ( ! productId ) {
		throw new Error(
			`Failed to find ${ productName }: ${ productIdOutput.stdout }`
		);
	}
	await wpCLI(
		`eval '$product = wc_get_product( ${ productId } ); $product->set_stock_status( "outofstock" ); $product->save();'`
	);
};

test.describe( 'Shopper → Notice Templates', () => {
	test.beforeEach( async ( { requestUtils, frontendUtils } ) => {
		await requestUtils.activateTheme( CLASSIC_THEME_SLUG );

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

	test( 'success and error notices use the classic templates on a classic theme', async ( {
		frontendUtils,
		page,
	} ) => {
		await frontendUtils.goToCartShortcode();
		await page.getByPlaceholder( 'Coupon code' ).fill( 'testcoupon' );
		await page.getByRole( 'button', { name: 'Apply coupon' } ).click();

		await expect(
			page.locator( '.woocommerce-notices-wrapper .woocommerce-message' )
		).toContainText( 'Coupon code applied successfully.' );

		await setProductOutOfStock( REGULAR_PRICED_PRODUCT_NAME );
		await page.reload();

		await expect(
			page.locator( '.woocommerce-notices-wrapper .woocommerce-error' )
		).toContainText(
			`Sorry, "${ REGULAR_PRICED_PRODUCT_NAME }" is not in stock.`
		);
		await expect(
			page.locator( '.wc-block-components-notice-banner' )
		).toHaveCount( 0 );
	} );

	test( 'success and error notices use the block templates when a classic theme opts in', async ( {
		requestUtils,
		frontendUtils,
		page,
	} ) => {
		await requestUtils.activateTheme(
			CLASSIC_CHILD_THEME_WITH_BLOCK_NOTICES_FILTER_SLUG
		);

		await frontendUtils.goToCartShortcode();
		await page.getByPlaceholder( 'Coupon code' ).fill( 'testcoupon' );
		await page.getByRole( 'button', { name: 'Apply coupon' } ).click();

		await expect(
			page.getByText( 'Coupon code applied successfully.' )
		).toBeVisible();
		await expect(
			page.locator( '.wc-block-components-notice-banner.is-success svg' )
		).toBeVisible();

		await page.reload();
		await page.getByPlaceholder( 'Coupon code' ).fill( 'testcoupon' );
		await page.getByRole( 'button', { name: 'Apply coupon' } ).click();

		await expect(
			page.getByText( 'Coupon code "testcoupon" already applied!' )
		).toBeVisible();

		// A non-coupon error: the cart holds a product the store has run out of.
		await setProductOutOfStock( REGULAR_PRICED_PRODUCT_NAME );
		await page.reload();

		const errorBanner = page.locator(
			'.wc-block-components-notice-banner.is-error'
		);
		await expect( errorBanner ).toContainText(
			`Sorry, "${ REGULAR_PRICED_PRODUCT_NAME }" is not in stock.`
		);
		await expect( errorBanner.locator( 'svg' ) ).toBeVisible();

		await page.getByLabel( 'Remove Polo from cart' ).click();

		await expect(
			page.getByText( 'Your cart is currently empty.' )
		).toBeVisible();
		await expect(
			page.locator( '.wc-block-components-notice-banner.is-success svg' )
		).toBeVisible();

		await requestUtils.activateTheme( CLASSIC_THEME_SLUG );
	} );
} );
