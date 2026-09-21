/**
 * External dependencies
 */
import { expect, test as base, guestFile } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { REGULAR_PRICED_PRODUCT_NAME } from './constants';
import { CheckoutPage } from './checkout.page';

const test = base.extend< { checkoutPageObject: CheckoutPage } >( {
	checkoutPageObject: async ( { page }, use ) => {
		const pageObject = new CheckoutPage( {
			page,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Shopper → Extensibility', () => {
	test.use( { storageState: guestFile } );

	test.beforeEach( async ( { requestUtils, frontendUtils } ) => {
		await requestUtils.rest( {
			method: 'PUT',
			path: 'wc/v3/settings/account/woocommerce_enable_guest_checkout',
			data: { value: 'yes' },
		} );
		await requestUtils.rest( {
			method: 'PUT',
			path: 'wc/v3/settings/account/woocommerce_enable_checkout_login_reminder',
			data: { value: 'yes' },
		} );
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-extensioncartupdate'
		);

		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToCheckout();
	} );

	test.describe( 'extensionCartUpdate', () => {
		test( 'Cart data can be modified by extensions', async ( {
			checkoutPageObject,
		} ) => {
			const { page } = checkoutPageObject;

			// Fill in the address, then wait until it has reached the server. The dirty flag is
			// not enough on its own: it is cleared by whichever push finishes first, which can
			// be an earlier one that did not carry the address.
			await checkoutPageObject.fillInCheckoutWithTestData();
			await expect
				.poll(
					async () =>
						page.evaluate( async () => {
							const response = await fetch(
								'/wp-json/wc/store/v1/cart'
							);
							if ( ! response.ok ) {
								return null;
							}
							const cart = await response.json();
							const postcode = cart?.shipping_address?.postcode;
							return typeof postcode === 'string'
								? postcode
								: null;
						} ),
					{ timeout: 15000 }
				)
				.toBe( '90210' );

			// A postcode that fails validation is never pushed, so it only exists in the browser.
			// A country change is not usable here: picking one pushes straight away so shipping
			// can be recalculated, which would leave nothing unpushed to overwrite.
			const postcode = page.locator( '#shipping-postcode' );
			await postcode.fill( 'ABCDEF' );
			await postcode.blur();
			await page.waitForFunction( () => {
				return (
					window.localStorage.getItem(
						'WOOCOMMERCE_CHECKOUT_IS_CUSTOMER_DATA_DIRTY'
					) === 'true'
				);
			} );

			// Without the arg, the unpushed postcode is kept.
			await page.evaluate( () =>
				window.wc.blocksCheckout.extensionCartUpdate( {
					namespace: 'woocommerce-blocks-test-extension-cart-update',
				} )
			);
			await expect( postcode ).toHaveValue( 'ABCDEF' );

			// With overwriteDirtyCustomerData, the address from the server replaces it.
			const overwriteResponse = await page.evaluate( () =>
				window.wc.blocksCheckout.extensionCartUpdate( {
					namespace: 'woocommerce-blocks-test-extension-cart-update',
					overwriteDirtyCustomerData: true,
				} )
			);
			expect( overwriteResponse.shipping_address.postcode ).toBe(
				'90210'
			);
			await expect( postcode ).toHaveValue( '90210' );

			await page.evaluate( () =>
				window.wc.blocksCheckout.extensionCartUpdate( {
					namespace: 'woocommerce-blocks-test-extension-cart-update',
					data: { 'test-name-change': true },
					overwriteDirtyCustomerData: true,
				} )
			);
			await expect( page.getByLabel( 'First name' ) ).toHaveValue(
				'Mr. Test'
			);
		} );
	} );
} );
