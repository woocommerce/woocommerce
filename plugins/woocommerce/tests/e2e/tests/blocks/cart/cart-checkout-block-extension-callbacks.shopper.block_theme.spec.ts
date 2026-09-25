/**
 * External dependencies
 */
import { expect, test as base } from '@woocommerce/e2e-utils';

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

test.describe( 'Shopper → Cart Extension Callbacks', () => {
	test( 'Custom error code creates exception', async ( {
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

		await expect(
			page.evaluate( () =>
				window.wc.blocksCheckout
					.extensionCartUpdate( {
						namespace: 'cart-extensions-test-helper',
					} )
					.catch( ( error ) => {
						throw new Error( error.message );
					} )
			)
		).rejects.toThrow( 'This is an error with cart context.' );
	} );

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

	test( 'Invalid callback namespace creates notice', async ( {
		frontendUtils,
		page,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToCart();

		await page.evaluate( () => {
			window.wc.blocksCheckout.extensionCartUpdate( {
				namespace: 'invalid-namespace',
			} );
		} );

		await expect(
			page
				.locator( '.wc-block-components-notice-banner__content' )
				.getByText(
					'There is no such namespace registered: invalid-namespace.'
				)
		).toBeVisible();
	} );
} );
