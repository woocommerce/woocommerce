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

	async fillInCheckoutWithTestData(
		overrideData = {},
		additionalFields: {
			address?: {
				shipping?: Record< string, string >;
				billing?: Record< string, string >;
			};
			contact?: Record< string, string >;
			order?: Record< string, string >;
		} = {
			address: { shipping: {}, billing: {} },
			order: {},
			contact: {},
		}
	) {
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

		await this.fillContactInformation(
			testData.email,
			additionalFields.contact || {}
		);
		if ( isShippingOpen ) {
			await this.fillShippingDetails(
				testData,
				additionalFields.address?.shipping || {}
			);
		}
		if ( isBillingOpen ) {
			// Additional billing details
			await this.fillBillingDetails( testData, {
				...( additionalFields.address?.shipping || {} ),
				...( additionalFields.address?.billing || {} ),
			} );
		}

		if (
			typeof additionalFields.order !== 'undefined' &&
			Object.keys( additionalFields.order ).length > 0
		) {
			await this.fillAdditionalInformationSection(
				additionalFields.order
			);
		}

		// Blur active field to trigger shipping rates update, then wait for requests to finish.
		await this.page.evaluate( 'document.activeElement.blur()' );
	}

	async fillContactInformation(
		email: string,
		additionalFields: Record< string, string >
	) {
		const contactSection = this.page.getByRole( 'group', {
			name: 'Contact information',
		} );
		await contactSection.getByLabel( 'Email address' ).fill( email );

		// Rest of additional data passed in from the overrideData object.
		for ( const [ label, value ] of Object.entries( additionalFields ) ) {
			const field = contactSection.getByLabel( label );
			await field.fill( value );
		}
	}

	async fillAdditionalInformationSection(
		additionalFields: Record< string, string >
	) {
		const contactSection = this.page.getByRole( 'group', {
			name: 'Additional order information',
		} );

		// Rest of additional data passed in from the overrideData object.
		for ( const [ label, value ] of Object.entries( additionalFields ) ) {
			const field = contactSection.getByLabel( label );
			await field.fill( value );
		}
	}

	async fillAdditionalInformation(
		email: string,
		additionalFields: { label: string; value: string }[]
	) {
		await this.page.getByLabel( 'Email address' ).fill( email );

		// Rest of additional data passed in from the overrideData object.
		for ( const { label, value } of additionalFields ) {
			const field = this.page.getByLabel( label );
			await field.fill( value );
		}
	}

	/**
	 * Blurs the current input and waits for the checkout to finish any loading or calculating.
	 */
	async waitForCheckoutToFinishUpdating() {
		await this.page.evaluate( 'document.activeElement.blur()' );

		await this.page.waitForFunction( () => {
			return (
				! window.wp.data
					.select( 'wc/store/checkout' )
					.isCalculating() &&
				! window.wp.data
					.select( 'wc/store/cart' )
					.isShippingRateBeingSelected() &&
				! window.wp.data
					.select( 'wc/store/cart' )
					.isCustomerDataUpdating()
			);
		} );
	}

	/**
	 * Place order and wait for redirect to order received page.
	 *
	 * @param waitForRedirect If false, then the method will not wait for the redirect to order received page. Useful
	 *                        when testing for errors on the checkout page.
	 */
	async placeOrder( waitForRedirect = true ) {
		await this.page
			.waitForRequest(
				( request ) => {
					return request.url().includes( 'batch' );
				},
				{ timeout: 3000 }
			)
			.catch( () => {
				// Do nothing. This is just in case there's a debounced request
				// still to be made, e.g. from checking "Can a truck fit down
				// your road?" field.
			} );
		await this.waitForCheckoutToFinishUpdating();
		await this.page.getByText( 'Place Order', { exact: true } ).click();
		if ( waitForRedirect ) {
			await this.page.waitForURL( /order-received/ );
		}
	}

	/**
	 * Verifies that the additional field values are visible on the confirmation page.
	 */
	async verifyAdditionalFieldsDetails( values: [ string, string ][] ) {
		for ( const [ label, value ] of values ) {
			const visible = await this.page
				.getByText(
					`${ label }${ value }` // No space between these due to the way the markup is output on the confirmation page.
				)
				.isVisible();

			if ( ! visible ) {
				return false;
			}
		}
		// If one of the fields above is false the function would have returned early.
		return true;
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

	async waitForCustomerDataUpdate() {
		// Wait for data to start updating.
		await this.page.waitForFunction( () => {
			return !! window.wp.data
				.select( 'wc/store/cart' )
				.isCustomerDataUpdating();
		} );

		// Wait for data to finish updating
		await this.page.waitForFunction( () => {
			return ! window.wp.data
				.select( 'wc/store/cart' )
				.isCustomerDataUpdating();
		} );
	}

	async editShippingDetails() {
		const editButton = this.page.locator(
			'.wc-block-checkout__shipping-fields .wc-block-components-address-address-wrapper:not(.is-editing) .wc-block-components-address-card__edit'
		);

		if ( await editButton.isVisible() ) {
			await editButton.click();
		}
	}

	async fillBillingDetails(
		customerBillingDetails: Record< string, string >,
		additionalFields: Record< string, string > = {}
	) {
		await this.editBillingDetails();
		const billingForm = this.page.getByRole( 'group', {
			name: 'Billing address',
		} );

		if ( customerBillingDetails.company ) {
			const companyInputField = billingForm.getByLabel( 'Company' );
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

		await email.fill( customerBillingDetails.email );
		await firstName.fill( customerBillingDetails.firstname );
		await lastName.fill( customerBillingDetails.lastname );
		await country.fill( customerBillingDetails.country );
		await address1.fill( customerBillingDetails.addressfirstline );
		await address2.fill( customerBillingDetails.addresssecondline );
		await city.fill( customerBillingDetails.city );
		await phone.fill( customerBillingDetails.phone );

		if ( customerBillingDetails.state ) {
			const state = billingForm.getByLabel( 'State', {
				exact: true,
			} );
			const province = billingForm.getByLabel( 'Province', {
				exact: true,
			} );
			const county = billingForm.getByLabel( 'County' );

			await state
				.or( province )
				.or( county )
				.fill( customerBillingDetails.state );
		}

		if ( customerBillingDetails.postcode ) {
			// Using locator here since the label of this form changes depending
			// on the country.
			const postcode = billingForm.locator( '#billing-postcode' );
			await postcode.fill( customerBillingDetails.postcode );
		}

		// Rest of additional data passed in from the overrideData object.
		for ( const [ label, value ] of Object.entries( additionalFields ) ) {
			const field = billingForm.getByLabel( label, { exact: true } );
			await field.fill( value );
		}
	}

	async fillShippingDetails(
		customerShippingDetails: Record< string, string >,
		additionalFields: Record< string, string > = {}
	) {
		await this.editShippingDetails();
		const shippingForm = this.page.getByRole( 'group', {
			name: 'Shipping address',
		} );

		if ( customerShippingDetails.company ) {
			const companyInputField = shippingForm.getByLabel( 'Company' );
			await companyInputField.fill( customerShippingDetails.company );
		}

		const firstName = shippingForm.getByLabel( 'First name' );
		const lastName = shippingForm.getByLabel( 'Last name' );
		const country = shippingForm.getByLabel( 'Country/Region' );
		const address1 = shippingForm.getByLabel( 'Address', { exact: true } );
		const address2 = shippingForm.getByLabel( 'Apartment, suite, etc.' );
		const city = shippingForm.getByLabel( 'City' );
		const phone = shippingForm.getByLabel( 'Phone' );

		await firstName.fill( customerShippingDetails.firstname );
		await lastName.fill( customerShippingDetails.lastname );
		await country.fill( customerShippingDetails.country );
		await address1.fill( customerShippingDetails.addressfirstline );
		await address2.fill( customerShippingDetails.addresssecondline );
		await city.fill( customerShippingDetails.city );
		await phone.fill( customerShippingDetails.phone );

		if ( customerShippingDetails.state ) {
			const state = shippingForm.getByLabel( 'State', {
				exact: true,
			} );
			const province = shippingForm.getByLabel( 'Province', {
				exact: true,
			} );
			const county = shippingForm.getByLabel( 'County' );

			await state
				.or( province )
				.or( county )
				.fill( customerShippingDetails.state );
		}

		if ( customerShippingDetails.postcode ) {
			// Using locator here since the label of this form changes depending
			// on the country.
			const postcode = shippingForm.locator( '#shipping-postcode' );
			await postcode.fill( customerShippingDetails.postcode );
		}

		// Rest of additional data passed in from the overrideData object.
		for ( const [ label, value ] of Object.entries( additionalFields ) ) {
			const field = shippingForm.getByLabel( label, { exact: true } );
			await field.fill( value );
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
				currentPage
					.locator(
						'table.wc-block-order-confirmation-totals__table '
					)
					.getByRole( 'link', {
						name: SIMPLE_VIRTUAL_PRODUCT_NAME,
					} )
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
