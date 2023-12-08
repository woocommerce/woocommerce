/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-playwright-utils';
import { customerFile, guestFile } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { CheckoutPage } from './checkout.page';
import {
	FREE_SHIPPING_NAME,
	FREE_SHIPPING_PRICE,
	SIMPLE_PHYSICAL_PRODUCT_NAME,
	FLAT_RATE_SHIPPING_NAME,
	FLAT_RATE_SHIPPING_PRICE,
} from './constants';

const test = base.extend< { pageObject: CheckoutPage } >( {
	pageObject: async ( { page }, use ) => {
		const pageObject = new CheckoutPage( {
			page,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Shopper → Checkout block → Shipping', () => {
	test.use( { storageState: customerFile } );

	test( 'Shopper can choose free shipping, flat rate shipping, and can have different billing and shipping addresses', async ( {
		pageObject,
		frontendUtils,
		page,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.emptyCart();
		await frontendUtils.addToCart( 'Beanie' );
		await frontendUtils.goToCheckout();
		await expect(
			await pageObject.selectAndVerifyShippingOption(
				FREE_SHIPPING_NAME,
				FREE_SHIPPING_PRICE
			)
		).toBe( true );
		await pageObject.fillInCheckoutWithTestData();
		await pageObject.placeOrder();
		await pageObject.verifyAddressDetails( 'billing' );
		await pageObject.verifyAddressDetails( 'shipping' );
		await expect( page.getByText( FREE_SHIPPING_NAME ) ).toBeVisible();

		await frontendUtils.goToShop();
		await frontendUtils.addToCart( 'Beanie' );
		await frontendUtils.goToCheckout();
		await expect(
			await pageObject.selectAndVerifyShippingOption(
				FLAT_RATE_SHIPPING_NAME,
				FLAT_RATE_SHIPPING_PRICE
			)
		).toBe( true );

		await pageObject.syncBillingWithShipping();
		await pageObject.fillInCheckoutWithTestData( {
			phone: '0987654322',
		} );
		await pageObject.unsyncBillingWithShipping();
		const shippingForm = page.getByRole( 'group', {
			name: 'Shipping address',
		} );
		const billingForm = page.getByRole( 'group', {
			name: 'Billing address',
		} );

		await expect( shippingForm.getByLabel( 'Phone' ).inputValue ).toEqual(
			billingForm.getByLabel( 'Phone' ).inputValue
		);

		await pageObject.fillInCheckoutWithTestData();
		const overrideBillingDetails = {
			firstname: 'Juan',
			lastname: 'Perez',
			addressfirstline: '123 Test Street',
			addresssecondline: 'Apartment 6',
			country: 'ES',
			city: 'Madrid',
			postcode: '08830',
			state: 'M',
			phone: '0987654321',
			email: 'juan.perez@test.com',
		};
		await pageObject.fillBillingDetails( overrideBillingDetails );
		await pageObject.placeOrder();
		await pageObject.verifyAddressDetails(
			'billing',
			overrideBillingDetails
		);
		await pageObject.verifyAddressDetails( 'shipping' );
	} );
} );

// We only check if guest user can place an order because we already checked if logged in user can
// place an order in the previous test
test.describe( 'Shopper → Checkout block → Place Order', () => {
	test.use( { storageState: guestFile } );

	test( 'Guest user can place order', async ( {
		pageObject,
		frontendUtils,
		page,
	} ) => {
		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await frontendUtils.goToCheckout();
		await expect(
			await pageObject.selectAndVerifyShippingOption(
				FREE_SHIPPING_NAME,
				FREE_SHIPPING_PRICE
			)
		).toBe( true );
		await pageObject.fillInCheckoutWithTestData();
		await pageObject.placeOrder();
		await expect(
			page.getByText( 'Your order has been received.' )
		).toBeVisible();
	} );
} );
