/**
 * External dependencies
 */
import {
	expect,
	test as base,
	customerFile,
	guestFile,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import {
	SIMPLE_PHYSICAL_PRODUCT_NAME,
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

test.describe( 'Shopper → Local pickup', () => {
	test.beforeEach( async ( { localPickupUtils } ) => {
		await localPickupUtils.enableLocalPickup();
		await localPickupUtils.addPickupLocation( {
			location: {
				name: 'Testing',
				address: 'Test Address',
				city: 'Test City',
				postcode: '90210',
				state: 'US:CA',
				details: 'Pickup method.',
			},
		} );
	} );

	test( 'Switching between local pickup and shipping does not affect the address and is used for the order', async ( {
		page,
		frontendUtils,
		checkoutPageObject,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await frontendUtils.goToCheckout();

		await checkoutPageObject.selectDeliveryOption( 'Pickup' );
		await page
			.getByLabel( 'Email address' )
			.fill( 'thisShouldRemainHere@mail.com' );
		await expect( page.getByLabel( 'Email address' ) ).toHaveValue(
			'thisShouldRemainHere@mail.com'
		);

		await checkoutPageObject.selectDeliveryOption( 'Ship' );
		await expect( page.getByLabel( 'Email address' ) ).toHaveValue(
			'thisShouldRemainHere@mail.com'
		);

		await checkoutPageObject.fillInCheckoutWithTestData();

		await checkoutPageObject.selectDeliveryOption( 'Pickup' );
		await expect( page.getByLabel( 'Email address' ) ).toHaveValue(
			'john.doe@test.com'
		);

		await checkoutPageObject.selectDeliveryOption( 'Ship' );
		await expect( page.getByLabel( 'Email address' ) ).toHaveValue(
			'john.doe@test.com'
		);

		await checkoutPageObject.selectDeliveryOption( 'Pickup' );
		await expect( page.getByText( 'Pickup (Testing)' ) ).toBeVisible();

		await checkoutPageObject.placeOrder();

		await expect(
			page.getByText( 'Thank you. Your order has been received.' )
		).toBeVisible();

		await expect(
			// The regex pattern matches "Collection from Testing" followed by any characters (.*)
			page.getByRole( 'cell', { name: /Collection from Testing.*/ } )
		).toBeVisible();
		await checkoutPageObject.verifyBillingDetails();
	} );
} );

test.describe( 'Shopper → Shipping (customer user)', () => {
	test.use( { storageState: customerFile } );

	test( 'Shopper can choose free shipping, flat rate shipping, and can have different billing and shipping addresses', async ( {
		checkoutPageObject,
		frontendUtils,
		page,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( 'Beanie' );
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

		await frontendUtils.goToShop();
		await frontendUtils.addToCart( 'Beanie' );
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

		const syncedShippingPhone = await shippingForm
			.getByLabel( 'Phone' )
			.inputValue();
		const syncedBillingPhone = await billingForm
			.getByLabel( 'Phone' )
			.inputValue();
		expect( syncedShippingPhone ).toBe( '0987654322' );
		expect( syncedBillingPhone ).toBe( syncedShippingPhone );

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
		const finalShippingPhone = await shippingForm
			.getByLabel( 'Phone' )
			.inputValue();
		const finalBillingPhone = await billingForm
			.getByLabel( 'Phone' )
			.inputValue();
		expect( finalBillingPhone ).toBe( overrideBillingDetails.phone );
		expect( finalShippingPhone ).not.toBe( finalBillingPhone );
		await checkoutPageObject.placeOrder();
		await checkoutPageObject.verifyAddressDetails(
			'billing',
			overrideBillingDetails
		);
		await checkoutPageObject.verifyAddressDetails( 'shipping' );
	} );
} );

test.describe( 'Shopper → Store shipping disabled', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.rest( {
			method: 'PUT',
			path: 'wc/v3/settings/general/woocommerce_ship_to_countries',
			data: { value: 'disabled' },
		} );
	} );

	test( 'can place a physical order when store shipping is disabled', async ( {
		checkoutPageObject,
		frontendUtils,
		localPickupUtils,
		page,
	} ) => {
		await localPickupUtils.disableLocalPickup();

		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await frontendUtils.goToCart();

		await expect(
			page.getByText( 'Delivery', { exact: true } )
		).toBeHidden();

		await frontendUtils.goToCheckout();

		// Delivery total in the sidebar.
		await expect(
			page.getByText( 'Delivery', { exact: true } )
		).toBeHidden();

		// Ship/Pickup method selector.
		await expect( page.getByText( 'Ship', { exact: true } ) ).toBeHidden();
		await expect(
			page.getByText( 'Pickup', { exact: true } )
		).toBeHidden();

		await checkoutPageObject.fillInCheckoutWithTestData();
		await checkoutPageObject.placeOrder();

		await expect(
			page.getByText( 'Thank you. Your order has been received.' )
		).toBeVisible();
	} );
} );

test.describe( 'Shopper → Checkout Form Errors (guest user)', () => {
	test.use( { storageState: guestFile } );

	test( 'can see errors when form is incomplete', async ( {
		frontendUtils,
		page,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await frontendUtils.goToCheckout();

		await page.getByLabel( 'Email address' ).clear();
		// Notices on the email field will move content when the field loses focus. This can cause the click to "miss".
		await page.getByRole( 'button', { name: 'Place order' } ).focus();
		await page.getByRole( 'button', { name: 'Place order' } ).click();

		// Verify that all required fields show the correct warning.
		await expect(
			page.getByText( 'Please enter a valid email address' )
		).toBeVisible();
		await expect(
			page.getByText( 'Please enter a valid first name' )
		).toBeVisible();
		await expect(
			page.getByText( 'Please enter a valid last name' )
		).toBeVisible();
		await expect(
			page.getByText( 'Please enter a valid address' )
		).toBeVisible();
		await expect(
			page.getByText( 'Please enter a valid city' )
		).toBeVisible();
		await expect(
			page.getByText( 'Please enter a valid zip code' )
		).toBeVisible();
		await expect( page.getByLabel( 'Email address' ) ).toBeFocused();
	} );
} );

test.describe( 'Billing Address Form', () => {
	test.describe( 'Guest user', () => {
		test.use( { storageState: guestFile } );

		test( 'Ensure billing is empty and shipping address is filled', async ( {
			frontendUtils,
			page,
			checkoutPageObject,
		} ) => {
			await frontendUtils.goToShop();
			await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
			await frontendUtils.goToCheckout();

			await checkoutPageObject.fillShippingDetails( {
				firstname: 'John',
				lastname: 'Doe',
				addressfirstline: '123 Easy Street',
				addresssecondline: 'Testville',
				country: 'United States (US)',
				countryKey: 'US',
				city: 'New York',
				state: 'New York',
				postcode: '90210',
				phone: '01234567890',
			} );

			const shippingForm = page.getByRole( 'group', {
				name: 'Shipping address',
			} );

			await page.getByLabel( 'Use same address for billing' ).uncheck();

			await expect( shippingForm.getByLabel( 'First name' ) ).toHaveValue(
				'John'
			);
			await expect( shippingForm.getByLabel( 'Last name' ) ).toHaveValue(
				'Doe'
			);
			await expect(
				shippingForm.getByLabel( 'Address', { exact: true } )
			).toHaveValue( '123 Easy Street' );
			await expect(
				shippingForm.getByLabel( 'Apartment, suite, etc. (optional)' )
			).toHaveValue( 'Testville' );
			await expect(
				shippingForm.getByLabel( 'Country/Region' )
			).toHaveValue( 'US' );
			await expect( shippingForm.getByLabel( 'City' ) ).toHaveValue(
				'New York'
			);
			await expect( shippingForm.getByLabel( 'State' ) ).toHaveValue(
				'NY'
			);
			await expect( shippingForm.getByLabel( 'ZIP Code' ) ).toHaveValue(
				'90210'
			);
			await expect(
				shippingForm.getByLabel( 'Phone (optional)' )
			).toHaveValue( '01234567890' );

			const billingForm = page.getByRole( 'group', {
				name: 'Billing address',
			} );

			await expect( billingForm.getByLabel( 'First name' ) ).toHaveValue(
				''
			);
			await expect( billingForm.getByLabel( 'Last name' ) ).toHaveValue(
				''
			);
			await expect(
				billingForm.getByLabel( 'Address', { exact: true } )
			).toHaveValue( '' );
			await expect(
				billingForm.getByRole( 'button', {
					name: '+ Add apartment, suite, etc.',
				} )
			).toBeVisible();
			await expect(
				billingForm.getByLabel( 'Country/Region' )
			).toHaveValue( 'US' );
			await expect( billingForm.getByLabel( 'City' ) ).toHaveValue( '' );
			await expect( billingForm.getByLabel( 'State' ) ).toHaveValue(
				'NY'
			);
			await expect( billingForm.getByLabel( 'ZIP Code' ) ).toHaveValue(
				''
			);
			await expect(
				billingForm.getByLabel( 'Phone (optional)' )
			).toHaveValue( '' );
		} );
	} );
} );
