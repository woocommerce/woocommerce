/**
 * External dependencies
 */
import {
	expect,
	test as base,
	guestFile,
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

test.describe(
	'Checkout Block → Locale hides country and address fields',
	() => {
		test.use( { storageState: guestFile } );

		test.beforeEach( async ( { requestUtils, frontendUtils } ) => {
			await requestUtils.activatePlugin(
				'woocommerce-blocks-test-locale-hide-country'
			);
			await requestUtils.rest( {
				method: 'PUT',
				path: 'wc/v3/settings/account/woocommerce_enable_guest_checkout',
				data: { value: 'yes' },
			} );

			await frontendUtils.goToShop();
			await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
			await frontendUtils.goToCheckout();
		} );

		test.afterEach( async ( { requestUtils } ) => {
			await requestUtils.deactivatePlugin(
				'woocommerce-blocks-test-locale-hide-country'
			);
		} );

		test( 'Hidden address fields are not visible and country is not shown', async ( {
			page,
		} ) => {
			const shippingForm = page.getByRole( 'group', {
				name: 'Shipping address',
			} );

			await expect( shippingForm ).toBeVisible();

			// Country field should be hidden.
			await expect(
				shippingForm.getByLabel( 'Country/Region' )
			).toBeHidden();

			// Other locale-hidden fields should also be hidden.
			await expect(
				shippingForm.getByLabel( 'City' )
			).toBeHidden();
			await expect(
				shippingForm.getByLabel( 'Address', { exact: true } )
			).toBeHidden();
			await expect(
				shippingForm.getByLabel( 'Phone' )
			).toBeHidden();

			// Name fields should still be visible (not hidden by locale).
			await expect(
				shippingForm.getByLabel( 'First name' )
			).toBeVisible();
			await expect(
				shippingForm.getByLabel( 'Last name' )
			).toBeVisible();
		} );

		test( 'Can complete checkout with hidden country using store base country', async ( {
			page,
			checkoutPageObject,
		} ) => {
			const shippingForm = page.getByRole( 'group', {
				name: 'Shipping address',
			} );

			await expect( shippingForm ).toBeVisible();

			// Fill only the visible fields (name + email).
			await page
				.getByLabel( 'Email address' )
				.fill( 'test-locale@example.com' );
			await shippingForm
				.getByLabel( 'First name' )
				.fill( 'John' );
			await shippingForm
				.getByLabel( 'Last name' )
				.fill( 'Doe' );

			await checkoutPageObject.placeOrder();

			await expect(
				page.getByText(
					'Thank you. Your order has been received.'
				)
			).toBeVisible();
		} );
	}
);
