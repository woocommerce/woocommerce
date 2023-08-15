/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import { expect } from '@woocommerce/e2e-playwright-utils';

export class CheckoutPage {
	private BLOCK_NAME = 'woocommerce/checkout';
	private page: Page;
	private testData = {
		...{
			firstname: 'John',
			lastname: 'Doe',
			addressfirstline: '123 Easy Street',
			addresssecondline: 'Testville',
			country: 'United States (US)',
			city: 'New York',
			state: 'New York',
			postcode: '90210',
			email: 'john.doe@test.com',
			phone: '01234567890',
		},
	};

	constructor( { page }: { page: Page } ) {
		this.page = page;
	}

	async isShippingRateSelected(
		shippingName: string,
		shippingPrice: string
	) {
		const shippingLine = this.page.locator(
			'.wc-block-components-totals-shipping'
		);

		const nameIsVisible = await shippingLine
			.getByText( shippingName )
			.isVisible();
		const priceIsVisible = await shippingLine
			.getByText( shippingPrice )
			.isVisible();
		return nameIsVisible && priceIsVisible;
	}

	async fillInCheckoutWithTestData( overrideData = {} ) {
		const isShippingOpen = await this.page
			.getByRole( 'group', {
				name: 'Shipping address',
			} )
			.isVisible();

		const isBillingOpen = await this.page
			.getByRole( 'group', {
				name: 'Billing address',
			} )
			.isVisible();

		const testData = { ...this.testData, ...overrideData };

		await this.page.getByLabel( 'Email address' ).fill( testData.email );
		if ( isShippingOpen ) {
			await this.fillShippingDetails( testData );
		}
		if ( isBillingOpen ) {
			await this.fillBillingDetails( testData );
		}
		// Blur active field to trigger shipping rates update, then wait for requests to finish.
		await this.page.evaluate( 'document.activeElement.blur()' );
	}

	async placeOrder() {
		await this.page.getByText( 'Place Order', { exact: true } ).click();
		await this.page.waitForURL( /order-received/ );
	}
	async verifyAddressDetails(
		shippingOrBilling: 'shipping' | 'billing',
		overrideAddressDetails = {}
	) {
		const customerAddressDetails = {
			...this.testData,
			...overrideAddressDetails,
		};
		const selector = `.woocommerce-column--${ shippingOrBilling }-address`;
		const addressContainer = this.page.locator( selector );
		await expect(
			addressContainer.getByText( customerAddressDetails.firstname )
		).toBeVisible();
		await expect(
			addressContainer.getByText( customerAddressDetails.lastname )
		).toBeVisible();
		await expect(
			addressContainer.getByText(
				customerAddressDetails.addressfirstline
			)
		).toBeVisible();
		await expect(
			addressContainer.getByText(
				customerAddressDetails.addresssecondline
			)
		).toBeVisible();
		await expect(
			addressContainer.getByText( customerAddressDetails.city )
		).toBeVisible();
		await expect(
			addressContainer.getByText( customerAddressDetails.state )
		).toBeVisible();
		await expect(
			addressContainer.getByText( customerAddressDetails.postcode )
		).toBeVisible();
		if ( shippingOrBilling === 'billing' ) {
			await expect(
				addressContainer.getByText( customerAddressDetails.phone )
			).toBeVisible();
		}
	}

	async fillBillingDetails( customerBillingDetails ) {
		const billingForm = this.page.getByRole( 'group', {
			name: 'Billing address',
		} );
		const companyInputField = billingForm.getByLabel( 'Company' );

		if ( await companyInputField.isVisible() ) {
			await companyInputField.fill( customerBillingDetails.company );
		}

		const email = this.page.getByLabel( 'Email address' );
		const firstName = billingForm.getByLabel( 'First name' );
		const lastName = billingForm.getByLabel( 'Last name' );
		const country = billingForm.getByLabel( 'Country/Region' );
		const address1 = billingForm.getByLabel( 'Address', { exact: true } );
		const address2 = billingForm.getByLabel( 'Apartment, suite, etc.' );
		const city = billingForm.getByLabel( 'City' );
		const state = billingForm.getByLabel( 'State', { exact: true } );
		const phone = billingForm.getByLabel( 'Phone' );

		// Using locator here since the label of this form changes depending on the country.
		const postcode = billingForm.locator( '#billing-postcode' );

		await email.fill( customerBillingDetails.email );
		await firstName.fill( customerBillingDetails.firstname );
		await lastName.fill( customerBillingDetails.lastname );
		await country.fill( customerBillingDetails.country );
		await address1.fill( customerBillingDetails.addressfirstline );
		await address2.fill( customerBillingDetails.addresssecondline );
		await city.fill( customerBillingDetails.city );
		await phone.fill( customerBillingDetails.phone );

		if ( await state.isVisible() ) {
			await state.fill( customerBillingDetails.state );
		}
		if ( await postcode.isVisible() ) {
			await postcode.fill( customerBillingDetails.postcode );
		}
		// Blur active field to trigger customer address update.
		await this.page.evaluate( 'document.activeElement.blur()' );
	}

	async fillShippingDetails( customerShippingDetails ) {
		const shippingForm = this.page.getByRole( 'group', {
			name: 'Shipping address',
		} );
		const companyInputField = shippingForm.getByLabel( 'Company' );

		if ( await companyInputField.isVisible() ) {
			await companyInputField.fill( customerShippingDetails.company );
		}

		const firstName = shippingForm.getByLabel( 'First name' );
		const lastName = shippingForm.getByLabel( 'Last name' );
		const country = shippingForm.getByLabel( 'Country/Region' );
		const address1 = shippingForm.getByLabel( 'Address', { exact: true } );
		const address2 = shippingForm.getByLabel( 'Apartment, suite, etc.' );
		const city = shippingForm.getByLabel( 'City' );
		const state = shippingForm.getByLabel( 'State', { exact: true } );
		const phone = shippingForm.getByLabel( 'Phone' );

		// Using locator here since the label of this form changes depending on the country.
		const postcode = shippingForm.locator( '#shipping-postcode' );

		await firstName.fill( customerShippingDetails.firstname );
		await lastName.fill( customerShippingDetails.lastname );
		await country.fill( customerShippingDetails.country );
		await address1.fill( customerShippingDetails.addressfirstline );
		await address2.fill( customerShippingDetails.addresssecondline );
		await city.fill( customerShippingDetails.city );
		await phone.fill( customerShippingDetails.phone );

		if ( await state.isVisible() ) {
			await state.fill( customerShippingDetails.state );
		}
		if ( await postcode.isVisible() ) {
			await postcode.fill( customerShippingDetails.postcode );
		}
		// Blur active field to trigger customer address update.
		await this.page.evaluate( 'document.activeElement.blur()' );
	}

	async selectAndVerifyShippingOption(
		shippingName: string,
		shippingPrice: string
	) {
		const shipping = this.page.getByLabel( shippingName );
		await expect( shipping ).toBeVisible();
		if (
			! ( await this.isShippingRateSelected(
				shippingName,
				shippingPrice
			) )
		) {
			await shipping.click();
			await this.page.waitForResponse( ( request ) => {
				const url = request.url();
				return url.includes( 'wc/store/v1/batch' );
			} );
			await this.page.waitForFunction( () => {
				return ! window.wp.data
					.select( 'wc/store/cart' )
					.isShippingRateBeingSelected();
			} );
		}
		return await this.isShippingRateSelected( shippingName, shippingPrice );
	}
}
