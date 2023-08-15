/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-playwright-utils';

/**
 * Internal dependencies
 */
import { CheckoutPage } from './checkout.page';

const test = base.extend< { pageObject: CheckoutPage } >( {
	pageObject: async ( { page }, use ) => {
		const pageObject = new CheckoutPage( {
			page,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Shopper → Checkout block → Shipping', () => {
	const FREE_SHIPPING_NAME = 'Free shipping';
	const FREE_SHIPPING_PRICE = '$0.00';
	const FLAT_RATE_SHIPPING_NAME = 'Flat rate shipping';
	const FLAT_RATE_SHIPPING_PRICE = '$10.00';

	test.use( {
		storageState: process.env.CUSTOMERSTATE,
	} );

	test( 'Shopper can choose free shipping, flat rate shipping, and can have different billing and shipping addresses', async ( {
		pageObject,
		frontendUtils,
		page,
	} ) => {
		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
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
		await page.getByLabel( 'Use same address for billing' ).uncheck();
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
		await expect( page.getByText( FLAT_RATE_SHIPPING_NAME ) ).toBeVisible();
	} );
} );
