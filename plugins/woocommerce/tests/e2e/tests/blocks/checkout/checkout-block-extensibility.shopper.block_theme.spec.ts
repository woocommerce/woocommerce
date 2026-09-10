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
		test( 'Response is not undefined in any code path', async ( {
			checkoutPageObject,
		} ) => {
			// With no additional args.
			let response = await checkoutPageObject.page.evaluate(
				"wc.blocksCheckout.extensionCartUpdate( { namespace: 'woocommerce-blocks-test-extension-cart-update' } ).then( ( response ) => response );"
			);
			let resolvedResponse = await Promise.resolve( response );
			expect( resolvedResponse ).not.toBeUndefined();
			expect( resolvedResponse ).toHaveProperty( 'billing_address' );

			// With overwriteDirtyCustomerData true.
			response = await checkoutPageObject.page.evaluate(
				"wc.blocksCheckout.extensionCartUpdate( { namespace: 'woocommerce-blocks-test-extension-cart-update', overwriteDirtyCustomerData: true } ).then( ( response ) => response );"
			);
			resolvedResponse = await Promise.resolve( response );
			expect( resolvedResponse ).not.toBeUndefined();
			expect( resolvedResponse ).toHaveProperty( 'billing_address' );

			// With overwriteDirtyCustomerData false.
			response = await checkoutPageObject.page.evaluate(
				"wc.blocksCheckout.extensionCartUpdate( { namespace: 'woocommerce-blocks-test-extension-cart-update', overwriteDirtyCustomerData: false } ).then( ( response ) => response );"
			);
			resolvedResponse = await Promise.resolve( response );
			expect( resolvedResponse ).not.toBeUndefined();
			expect( resolvedResponse ).toHaveProperty( 'billing_address' );

			// With a dirty customer object.
			await checkoutPageObject.page
				.getByLabel( 'Country/Region' )
				.selectOption( 'United Kingdom (UK)' );
			await expect(
				checkoutPageObject.page.getByLabel( 'Country/Region' )
			).toHaveValue( 'GB' );
			response = await checkoutPageObject.page.evaluate(
				"wc.blocksCheckout.extensionCartUpdate( { namespace: 'woocommerce-blocks-test-extension-cart-update' } ).then( ( response ) => response );"
			);
			resolvedResponse = await Promise.resolve( response );
			expect( resolvedResponse ).not.toBeUndefined();
			expect( resolvedResponse ).toHaveProperty( 'billing_address' );
		} );
		test( 'Unpushed data is/is not overwritten depending on arg', async ( {
			checkoutPageObject,
		} ) => {
			// Fill in the address, then wait until it has reached the server. The dirty flag is
			// not enough on its own: it is cleared by whichever push finishes first, which can
			// be an earlier one that did not carry the address.
			await checkoutPageObject.fillInCheckoutWithTestData();
			await expect
				.poll(
					async () =>
						checkoutPageObject.page.evaluate( async () => {
							const response = await fetch(
								'/wp-json/wc/store/v1/cart'
							);
							const cart = await response.json();
							return cart.shipping_address.postcode;
						} ),
					{ timeout: 15000 }
				)
				.toBe( '90210' );

			// A postcode that fails validation is never pushed, so it only exists in the browser.
			const postcode =
				checkoutPageObject.page.locator( '#shipping-postcode' );
			await postcode.fill( 'ABCDEF' );
			await postcode.blur();
			await checkoutPageObject.page.waitForFunction(
				() =>
					window.localStorage.getItem(
						'WOOCOMMERCE_CHECKOUT_IS_CUSTOMER_DATA_DIRTY'
					) === 'true'
			);

			// Without the arg, the unpushed postcode is kept.
			await checkoutPageObject.page.evaluate(
				"wc.blocksCheckout.extensionCartUpdate( { namespace: 'woocommerce-blocks-test-extension-cart-update' } )"
			);
			await expect( postcode ).toHaveValue( 'ABCDEF' );

			// With overwriteDirtyCustomerData, the address from the server replaces it.
			await checkoutPageObject.page.evaluate(
				"wc.blocksCheckout.extensionCartUpdate( { namespace: 'woocommerce-blocks-test-extension-cart-update', overwriteDirtyCustomerData: true } )"
			);
			await expect( postcode ).toHaveValue( '90210' );

			// Overwriting is possible now, but the address did push, so it is unchanged.
			await checkoutPageObject.page.evaluate(
				"wc.blocksCheckout.extensionCartUpdate( { namespace: 'woocommerce-blocks-test-extension-cart-update', overwriteDirtyCustomerData: true } )"
			);
			await expect(
				checkoutPageObject.page.getByLabel( 'Country/Region' )
			).toHaveValue( 'US' );
			await expect( postcode ).toHaveValue( '90210' );
		} );
		test( 'Cart data can be modified by extensions', async ( {
			checkoutPageObject,
		} ) => {
			await checkoutPageObject.fillInCheckoutWithTestData();
			await checkoutPageObject.page.waitForFunction( () => {
				return (
					window.localStorage.getItem(
						'WOOCOMMERCE_CHECKOUT_IS_CUSTOMER_DATA_DIRTY'
					) === 'false'
				);
			} );
			await checkoutPageObject.page.evaluate(
				"wc.blocksCheckout.extensionCartUpdate( { namespace: 'woocommerce-blocks-test-extension-cart-update', data: { 'test-name-change': true } } )"
			);
			await expect(
				checkoutPageObject.page.getByLabel( 'First name' )
			).toHaveValue( 'Mr. Test' );
		} );
	} );
} );
