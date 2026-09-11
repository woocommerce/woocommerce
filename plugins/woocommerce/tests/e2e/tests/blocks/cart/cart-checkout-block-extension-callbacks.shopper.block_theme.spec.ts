/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { REGULAR_PRICED_PRODUCT_NAME } from '../checkout/constants';

test.describe( 'Shopper → Cart Extension Callbacks', () => {
	test( 'Error code `woocommerce_rest_cart_extensions_error` creates notice', async ( {
		frontendUtils,
		requestUtils,
		page,
	} ) => {
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-cart-extensions'
		);

		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToCart();

		await page.evaluate( () => {
			window.wc.blocksCheckout.extensionCartUpdate( {
				namespace: 'cart-extensions-test-helper-2',
			} );
		} );

		await expect(
			page
				.locator( '.wc-block-components-notice-banner__content' )
				.getByText( 'This is an error with cart context.' )
		).toBeVisible();
	} );
} );
