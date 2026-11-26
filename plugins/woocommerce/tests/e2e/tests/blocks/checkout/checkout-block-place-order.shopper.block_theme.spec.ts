/**
 * External dependencies
 */
import { randomUUID } from 'crypto';
import { expect, test as base, guestFile } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import {
	FREE_SHIPPING_NAME,
	FREE_SHIPPING_PRICE,
	FLAT_RATE_SHIPPING_NAME,
	FLAT_RATE_SHIPPING_PRICE,
} from './constants';
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

test.describe( 'Shopper → Shipping (customer user)', () => {
	test.use( { storageState: guestFile } );

	// Logs in as a new customer. All workers share the cart of the seeded
	// customer, because `blocks-parallel` does not reset the database.
	test.beforeEach( async ( { page, requestUtils, cleanup } ) => {
		const username = `place-order-${ randomUUID().slice( 0, 8 ) }`;
		const password = 'password';
		const { id } = await requestUtils.rest< { id: number } >( {
			method: 'POST',
			path: 'wc/v3/customers',
			data: {
				username,
				password,
				email: `${ username }@example.com`,
			},
		} );
		cleanup( () =>
			requestUtils.rest( {
				method: 'DELETE',
				path: `wc/v3/customers/${ id }`,
				params: { force: true },
			} )
		);

		const login = await page.request.post( 'wp-login.php', {
			form: { log: username, pwd: password },
		} );
		// A failed login shows the form again instead of redirecting.
		expect( login.url() ).not.toContain( 'wp-login.php' );
	} );

	test( 'Shopper can choose free shipping, flat rate shipping, and can have different billing and shipping addresses', async ( {
		checkoutPageObject,
		frontendUtils,
		page,
		createHiddenProduct,
	} ) => {
		const { id: productId } = await createHiddenProduct( {
			name: 'Place order product',
			regular_price: '20',
		} );
		await page.goto( `/?add-to-cart=${ productId }` );
		await frontendUtils.goToCheckout();
		expect(
			await checkoutPageObject.selectAndVerifyShippingOption(
				FREE_SHIPPING_NAME,
				FREE_SHIPPING_PRICE
			)
		).toBe( true );
		await checkoutPageObject.fillInCheckoutWithTestData();
		await checkoutPageObject.placeOrder();
		await checkoutPageObject.verifyAddressDetails( 'billing' );
		await checkoutPageObject.verifyAddressDetails( 'shipping' );
		await expect( page.getByText( FREE_SHIPPING_NAME ) ).toBeVisible();

		await page.goto( `/?add-to-cart=${ productId }` );
		await frontendUtils.goToCheckout();
		expect(
			await checkoutPageObject.selectAndVerifyShippingOption(
				FLAT_RATE_SHIPPING_NAME,
				FLAT_RATE_SHIPPING_PRICE
			)
		).toBe( true );

		await checkoutPageObject.syncBillingWithShipping();
		await checkoutPageObject.fillInCheckoutWithTestData( {
			phone: '0987654322',
		} );
		await checkoutPageObject.unsyncBillingWithShipping();
		const shippingForm = page.getByRole( 'group', {
			name: 'Shipping address',
		} );
		const billingForm = page.getByRole( 'group', {
			name: 'Billing address',
		} );

		await expect( billingForm.getByLabel( 'Phone' ) ).toHaveValue(
			await shippingForm.getByLabel( 'Phone' ).inputValue()
		);

		await checkoutPageObject.fillInCheckoutWithTestData();
		const overrideBillingDetails = {
			firstname: 'Juan',
			lastname: 'Perez',
			addressfirstline: '123 Test Street',
			addresssecondline: 'Apartment 6',
			countryKey: 'ES',
			city: 'Madrid',
			postcode: '08830',
			state: 'M',
			phone: '0987654321',
			email: 'juan.perez@test.com',
		};
		await checkoutPageObject.fillBillingDetails( overrideBillingDetails );
		await checkoutPageObject.placeOrder();
		await checkoutPageObject.verifyAddressDetails(
			'billing',
			overrideBillingDetails
		);
		await checkoutPageObject.verifyAddressDetails( 'shipping' );
	} );
} );

test.describe( 'Shopper → Place Guest Order', () => {
	test.use( { storageState: guestFile } );

	test( 'Guest user can place order', async ( {
		checkoutPageObject,
		frontendUtils,
		page,
		createHiddenProduct,
	} ) => {
		const { id: productId } = await createHiddenProduct( {
			name: 'Place order product',
			regular_price: '20',
		} );
		await page.goto( `/?add-to-cart=${ productId }` );
		await frontendUtils.goToCheckout();
		expect(
			await checkoutPageObject.selectAndVerifyShippingOption(
				FREE_SHIPPING_NAME,
				FREE_SHIPPING_PRICE
			)
		).toBe( true );
		await checkoutPageObject.fillInCheckoutWithTestData();
		await checkoutPageObject.placeOrder();
		await expect(
			page.getByText( 'Your order has been received.' )
		).toBeVisible();
	} );
} );
