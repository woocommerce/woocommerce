/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import { expect } from '@woocommerce/e2e-playwright-utils';

/**
 * Internal dependencies
 */
import {
	FREE_SHIPPING_NAME,
	SIMPLE_PHYSICAL_PRODUCT_NAME,
	SIMPLE_VIRTUAL_PRODUCT_NAME,
} from './constants';

export class CheckoutPage {
	private BLOCK_NAME = 'woocommerce/checkout';
	public page: Page;
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

	/**
	 * Place order and wait for redirect to order received page.
	 *
	 * @param waitForRedirect If false, then the method will not wait for the redirect to order received page. Useful
	 *                        when testing for errors on the checkout page.
	 */
	async placeOrder( waitForRedirect = true ) {
		await this.page.getByText( 'Place Order', { exact: true } ).click();
		if ( waitForRedirect ) {
			await this.page.waitForURL( /order-received/ );
		}
	}

	async verifyAddressDetails(
		shippingOrBilling: 'shipping' | 'billing',
		overrideAddressDetails = {}
	) {
		const customerAddressDetails = {
			...this.testData,
			...overrideAddressDetails,
		};

		const legacySelector = `.woocommerce-column--${ shippingOrBilling }-address`;
		const blockSelector = `.wc-block-order-confirmation-${ shippingOrBilling }-address`;

		let addressContainer = this.page.locator( blockSelector );

		if ( ! ( await addressContainer.isVisible() ) ) {
			addressContainer = this.page.locator( legacySelector );
		}

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

	async editBillingDetails() {
		const editButton = this.page.locator(
			'.wc-block-checkout__billing-fields .wc-block-components-address-address-wrapper:not(.is-editing) .wc-block-components-address-card__edit'
		);

		if ( await editButton.isVisible() ) {
			await editButton.click();
		}
	}

	async editShippingDetails() {
		const editButton = this.page.locator(
			'.wc-block-checkout__shipping-fields .wc-block-components-address-address-wrapper:not(.is-editing) .wc-block-components-address-card__edit'
		);

		if ( await editButton.isVisible() ) {
			await editButton.click();
		}
	}

	async fillBillingDetails( customerBillingDetails ) {
		await this.editBillingDetails();
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

		let state = billingForm.getByLabel( 'State', {
			exact: true,
		} );

		if ( ! ( await state.isVisible() ) ) {
			state = billingForm.getByLabel( 'Province', {
				exact: true,
			} );
		}

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
		await this.editShippingDetails();
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

		let state = shippingForm.getByLabel( 'State', {
			exact: true,
		} );

		if ( ! ( await state.isVisible() ) ) {
			state = shippingForm.getByLabel( 'Province', {
				exact: true,
			} );
		}

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
		const shipping = this.page.getByLabel( shippingName ).first();
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

	async verifyOrderConfirmationDetails(
		currentPage: Page,
		toBeVisible = true
	) {
		const statusSection = currentPage.locator(
			'[data-block-name="woocommerce/order-confirmation-status"]'
		);
		const summarySection = currentPage.locator(
			'[data-block-name="woocommerce/order-confirmation-summary"]'
		);
		const totalsSection = currentPage.locator(
			'[data-block-name="woocommerce/order-confirmation-totals"]'
		);
		const shippingAddressSection = currentPage.locator(
			'[data-block-name="woocommerce/order-confirmation-shipping-address"]'
		);
		const billingAddressSection = currentPage.locator(
			'[data-block-name="woocommerce/order-confirmation-billing-address"]'
		);

		if ( toBeVisible ) {
			const {
				firstname,
				lastname,
				addressfirstline,
				addresssecondline,
				city,
				postcode,
				email,
				phone,
			} = this.testData;

			await expect( statusSection ).toBeVisible();
			await expect( summarySection ).toBeVisible();
			await expect( totalsSection ).toBeVisible();
			await expect( shippingAddressSection ).toBeVisible();
			await expect( billingAddressSection ).toBeVisible();

			// Confirm order data are visible and correct
			await expect(
				currentPage.getByText(
					'Thank you. Your order has been received.'
				)
			).toBeVisible();
			await expect( currentPage.getByText( email ) ).toBeVisible();
			await expect(
				currentPage.getByText( FREE_SHIPPING_NAME )
			).toBeVisible();
			await expect(
				currentPage.getByText( SIMPLE_PHYSICAL_PRODUCT_NAME )
			).toBeVisible();
			await expect(
				currentPage.getByText( SIMPLE_VIRTUAL_PRODUCT_NAME )
			).toBeVisible();
			await expect(
				currentPage
					.getByText(
						`${ firstname } ${ lastname }${ addressfirstline }${ addresssecondline }${ city }, NY ${ postcode }${ phone }`
					)
					.first()
			).toBeVisible();
			await expect(
				currentPage
					.getByText(
						`${ firstname } ${ lastname }${ addressfirstline }${ addresssecondline }${ city }, NY ${ postcode }${ phone }`
					)
					.nth( 1 )
			).toBeVisible();
		} else {
			await expect( statusSection ).toBeVisible();
			await expect( summarySection ).toBeHidden();
			await expect( totalsSection ).toBeHidden();
			await expect( shippingAddressSection ).toBeHidden();
			await expect( billingAddressSection ).toBeHidden();
		}
	}

	async syncBillingWithShipping() {
		await this.page.getByLabel( 'Use same address for billing' ).check();
	}

	async unsyncBillingWithShipping() {
		await this.page.getByLabel( 'Use same address for billing' ).uncheck();
	}

	getOrderId() {
		// Get the current URL
		const url = this.page.url();
		const urlObject = new URL( url );

		// Extract orderId from the pathname
		const pathnameSegments = urlObject.pathname.split( '/' );
		const orderId =
			pathnameSegments[
				pathnameSegments.indexOf( 'order-received' ) + 1
			];

		return orderId;
	}

	async verifyBillingDetails( overrideData = {} ) {
		const testData = { ...this.testData, ...overrideData };
		const {
			firstname,
			lastname,
			city,
			postcode,
			phone,
			addressfirstline,
			addresssecondline,
		} = testData;
		const billingAddressSection = this.page.locator(
			'[data-block-name="woocommerce/order-confirmation-billing-address"]'
		);
		await expect(
			billingAddressSection.getByText( `${ firstname } ${ lastname }` )
		).toBeVisible();
		await expect( billingAddressSection.getByText( city ) ).toBeVisible();
		await expect(
			billingAddressSection.getByText( postcode )
		).toBeVisible();
		await expect( billingAddressSection.getByText( phone ) ).toBeVisible();
		await expect(
			billingAddressSection.getByText( addressfirstline )
		).toBeVisible();
		await expect(
			billingAddressSection.getByText( addresssecondline )
		).toBeVisible();
	}
}
