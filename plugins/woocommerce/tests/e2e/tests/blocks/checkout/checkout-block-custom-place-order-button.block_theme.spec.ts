/**
 * External dependencies
 */
import {
	expect,
	test as base,
	enableTestPlugin,
	guestFile,
	wpCLI,
} from '@woocommerce/e2e-utils';

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

	test.beforeEach( async ( { context } ) => {
		await enableTestPlugin( context, 'custom-place-order-button-test' );
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
		requestUtils,
		frontendUtils,
		checkoutPageObject,
		cleanup,
	} ) => {
		// An order adds to the product's sales, so it buys a product of its own
		// and leaves the Best Sellers ranking alone. Hidden and dated in the
		// past, the product stays out of the catalog and New Arrivals.
		const { id: productId } = await requestUtils.rest< { id: number } >( {
			method: 'POST',
			path: 'wc/v3/products',
			data: {
				name: 'Custom place order button product',
				regular_price: '10',
				catalog_visibility: 'hidden',
				date_created: '2020-01-01T00:00:00',
			},
		} );
		cleanup( () =>
			requestUtils.rest( {
				method: 'DELETE',
				path: `wc/v3/products/${ productId }`,
				params: { force: true },
			} )
		);

		await page.goto( `/?add-to-cart=${ productId }` );
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

		const checkoutResponse = page.waitForResponse(
			( response ) =>
				response.url().includes( '/wc/store/v1/checkout' ) &&
				response.request().method() === 'POST'
		);

		// Focus then click the custom button to avoid misses due to page content shifting.
		await customButton.focus();
		await customButton.click();

		const { order_id: orderId } = await ( await checkoutResponse ).json();
		cleanup( () =>
			requestUtils.rest( {
				method: 'DELETE',
				path: `wc/v3/orders/${ orderId }`,
				params: { force: true },
			} )
		);

		// Verify order was placed successfully by checking for order confirmation.
		await expect( page ).toHaveURL( /order-received/ );
	} );
} );
