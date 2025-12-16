/**
 * External dependencies
 */
import { expect, test as base, guestFile, wpCLI } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { SIMPLE_PHYSICAL_PRODUCT_NAME } from './constants';
import { CheckoutPage } from './checkout.page';

const test = base.extend< { checkoutPageObject: CheckoutPage } >( {
	checkoutPageObject: async ( { page, requestUtils }, use ) => {
		const pageObject = new CheckoutPage( {
			page,
			requestUtils,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Custom Place Order Button', () => {
	test.use( { storageState: guestFile } );

	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-custom-place-order-button'
		);
		await wpCLI(
			'option set woocommerce_test-custom-button_settings --format=json \'{"enabled":"yes"}\''
		);
	} );

	test( 'clicking custom button triggers validation when form is invalid', async ( {
		page,
		frontendUtils,
		checkoutPageObject,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await frontendUtils.goToCheckout();

		// Verify the default place order button is visible.
		await expect(
			page.locator( '.wc-block-components-checkout-place-order-button' )
		).toBeVisible();

		// Select the test payment method.
		await page
			.getByRole( 'radio', { name: 'Test Custom Button Payment' } )
			.click();

		// Verify the default place order button is no longer visible.
		await expect(
			page.locator( '.wc-block-components-checkout-place-order-button' )
		).toBeHidden();

		// Fill in valid checkout data.
		await checkoutPageObject.fillInCheckoutWithTestData();

		// Clear any pre-filled fields to ensure validation fails.
		await page.getByLabel( 'Email address' ).clear();

		// Wait for the custom button to be enabled - in some cases the tests are so fast that shipping options haven't loaded yet.
		await expect(
			page.getByTestId( 'custom-place-order-button' )
		).toBeEnabled();

		// Click the custom button without filling required fields.
		await page.getByTestId( 'custom-place-order-button' ).click();

		// Verify validation errors are shown.
		await expect(
			page.locator( '.wc-block-components-validation-error' )
		).toBeVisible();
	} );

	test( 'clicking custom button submits order when form is valid', async ( {
		page,
		frontendUtils,
		checkoutPageObject,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await frontendUtils.goToCheckout();

		// Fill in valid checkout data.
		await checkoutPageObject.fillInCheckoutWithTestData();

		// Verify the default place order button is visible.
		await expect(
			page.locator( '.wc-block-components-checkout-place-order-button' )
		).toBeVisible();

		// Select the test payment method.
		await page
			.getByRole( 'radio', { name: 'Test Custom Button Payment' } )
			.click();

		// Verify the default place order button is not visible.
		await expect(
			page.locator( '.wc-block-components-checkout-place-order-button' )
		).toBeHidden();

		// Wait for the custom button to be enabled - in some cases the tests are so fast that shipping options haven't loaded yet.
		const customButton = page.getByTestId( 'custom-place-order-button' );
		await expect( customButton ).toBeEnabled();

		// Focus then click the custom button to avoid misses due to page content shifting.
		await customButton.focus();
		await customButton.click();

		// Verify order was placed successfully by checking for order confirmation.
		await expect( page ).toHaveURL( /order-received/ );
	} );
} );
