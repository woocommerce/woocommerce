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
		test( 'Unpushed data is/is not overwritten depending on arg', async ( {
			checkoutPageObject,
		} ) => {
			// First test by only partially filling in the address form.
			await checkoutPageObject.page
				.getByLabel( 'Country/Region' )
				.fill( 'United Kingdom (UK)' );
			await checkoutPageObject.page.getByLabel( 'Country/Region' ).blur();

			await checkoutPageObject.page.evaluate(
				"wc.blocksCheckout.extensionCartUpdate( { namespace: 'woocommerce-blocks-test-extension-cart-update' } )"
			);
			await expect(
				checkoutPageObject.page.getByLabel( 'Country/Region' )
			).toHaveValue( 'United Kingdom (UK)' );
			await checkoutPageObject.page.evaluate(
				"wc.blocksCheckout.extensionCartUpdate( { namespace: 'woocommerce-blocks-test-extension-cart-update', overwriteDirtyCustomerData: true } )"
			);
			await expect(
				checkoutPageObject.page.getByLabel( 'Country/Region' )
			).not.toHaveValue( 'United Kingdom (UK)' );

			// Next fully test the address form (so it pushes), then run extensionCartUpdate with
			// overwriteDirtyCustomerData: true so overwriting is possible, but since the address pushed it should not
			// be overwritten.
			await checkoutPageObject.fillInCheckoutWithTestData();
			await expect(
				checkoutPageObject.page.getByLabel( 'Country/Region' )
			).toHaveValue( 'United States (US)' );
			await checkoutPageObject.page.evaluate(
				"wc.blocksCheckout.extensionCartUpdate( { namespace: 'woocommerce-blocks-test-extension-cart-update', overwriteDirtyCustomerData: true } )"
			);
			await expect(
				checkoutPageObject.page.getByLabel( 'Country/Region' )
			).toHaveValue( 'United States (US)' );
		} );
		test( 'Cart data can be modified by extensions', async ( {
			checkoutPageObject,
		} ) => {
			await checkoutPageObject.fillInCheckoutWithTestData();
			await checkoutPageObject.page.waitForFunction( () => {
				console.log(
					window.wp.data
						.select( 'wc/store/cart' )
						.isCustomerDataDirty()
				);
				return (
					window.wp.data
						.select( 'wc/store/cart' )
						.isCustomerDataDirty() === false
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
