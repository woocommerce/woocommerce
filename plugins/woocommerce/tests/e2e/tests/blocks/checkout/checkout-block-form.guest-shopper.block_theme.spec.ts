/**
 * External dependencies
 */
import { expect, test as base, guestFile } from '@woocommerce/e2e-utils';

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
	} );
} );

test.describe( 'Shopper → Billing Address Form (guest user)', () => {
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
		await expect( shippingForm.getByLabel( 'Country/Region' ) ).toHaveValue(
			'US'
		);
		await expect( shippingForm.getByLabel( 'City' ) ).toHaveValue(
			'New York'
		);
		await expect( shippingForm.getByLabel( 'State' ) ).toHaveValue( 'NY' );
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
		await expect( billingForm.getByLabel( 'Last name' ) ).toHaveValue( '' );
		await expect(
			billingForm.getByLabel( 'Address', { exact: true } )
		).toHaveValue( '' );
		await expect(
			billingForm.getByRole( 'button', {
				name: '+ Add apartment, suite, etc.',
			} )
		).toBeVisible();
		await expect( billingForm.getByLabel( 'Country/Region' ) ).toHaveValue(
			'US'
		);
		await expect( billingForm.getByLabel( 'City' ) ).toHaveValue( '' );
		await expect( billingForm.getByLabel( 'State' ) ).toHaveValue( 'NY' );
		await expect( billingForm.getByLabel( 'ZIP Code' ) ).toHaveValue( '' );
		await expect(
			billingForm.getByLabel( 'Phone (optional)' )
		).toHaveValue( '' );
	} );
} );
