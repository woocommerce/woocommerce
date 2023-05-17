const { expect } = require( '@playwright/test' );

const STORE_DETAILS_URL = 'wp-admin/admin.php?page=wc-admin&path=/setup-wizard';
const INDUSTRY_DETAILS_URL =
	'wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard&step=industry';
const PRODUCT_TYPES_URL =
	'wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard&step=product-types';
const BUSIENSS_DETAILS_URL =
	'wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard&step=business-details';

const onboarding = {
	completeStoreDetailsSection: async ( page, store ) => {
		await page.goto( STORE_DETAILS_URL );
		// Type the requested country/region
		await page
			.locator( '#woocommerce-select-control-0__control-input' )
			.click();
		await page.fill(
			'#woocommerce-select-control-0__control-input',
			store.country
		);
		await page.locator( `button >> text=${ store.country }` ).click();
		// Fill store's address - first line
		await page.fill( '#inspector-text-control-0', store.address );
		// Fill postcode of the store
		await page.fill( '#inspector-text-control-1', store.zip );
		// Fill the city where the store is located
		await page.fill( '#inspector-text-control-2', store.city );
		// Fill store's email address
		await page.fill( '#inspector-text-control-3', store.email );
		// Verify that checkbox next to "Get tips, product updates and inspiration straight to your mailbox" is selected
		await page.locator( '#inspector-checkbox-control-0' ).check();
		// Click continue button
		await page.locator( 'button >> text=Continue' ).click();
		// Usage tracking dialog
		await page.textContent( '.components-modal__header-heading' );
		await page.locator( 'button >> text=No thanks' ).click();
		await page.waitForLoadState( 'networkidle' ); // not autowaiting for form submission
	},

	completeIndustrySection: async (
		page,
		industries,
		expectedNumberOfIndustries
	) => {
		await page.goto( INDUSTRY_DETAILS_URL );
		const pageHeading = await page.textContent(
			'div.woocommerce-profile-wizard__step-header > h2'
		);

		expect( pageHeading ).toContain(
			'In which industry does the store operate?'
		);
		// Check that there are the correct number of options listed
		const numCheckboxes = await page.$$(
			'.components-checkbox-control__input'
		);
		expect( numCheckboxes ).toHaveLength( expectedNumberOfIndustries );
		// Uncheck any currently checked industries
		for ( let i = 0; i < expectedNumberOfIndustries; i++ ) {
			const currentCheck = `#inspector-checkbox-control-${ i }`;
			await page.locator( currentCheck ).uncheck();
		}

		for ( let industry of Object.values( industries ) ) {
			await page.locator( `label >> text=${ industry }` ).click();
		}
	},

	handleSaveChangesModal: async ( page, { saveChanges } ) => {
		// Save changes? Modal
		await page.textContent( '.components-modal__header-heading' );

		if ( saveChanges ) {
			await page.locator( 'button >> text=Save' ).click();
		} else {
			await page.locator( 'button >> text=Discard' ).click();
		}
		await page.waitForLoadState( 'networkidle' );
	},

	completeProductTypesSection: async ( page, products ) => {
		// There are 7 checkboxes on the page, adjust this constant if we change that
		const expectedProductTypes = 7;
		await page.goto( PRODUCT_TYPES_URL );
		const pageHeading = await page.textContent(
			'div.woocommerce-profile-wizard__step-header > h2'
		);
		expect( pageHeading ).toContain(
			'What type of products will be listed?'
		);
		// Check that there are the correct number of options listed
		const numCheckboxes = await page.$$(
			'.components-checkbox-control__input'
		);
		expect( numCheckboxes ).toHaveLength( expectedProductTypes );
		// Uncheck any currently checked products
		for ( let i = 0; i < expectedProductTypes; i++ ) {
			const currentCheck = `#inspector-checkbox-control-${ i }`;
			await page.locator( currentCheck ).uncheck();
		}

		Object.keys( products ).forEach( async ( product ) => {
			await page
				.locator( `label >> text=${ products[ product ] }` )
				.click();
		} );
	},

	completeBusinessDetailsSection: async ( page ) => {
		await page.goto( BUSIENSS_DETAILS_URL );
		const pageHeading = await page.textContent(
			'div.woocommerce-profile-wizard__step-header > h2'
		);
		expect( pageHeading ).toContain( 'Tell us about your business' );
		// Select 1 - 10 for products
		await page
			.locator( '#woocommerce-select-control-0__control-input' )
			.click( {
				force: true,
			} );
		await page
			.locator( '#woocommerce-select-control__option-0-1-10' )
			.click();
		// Select No for selling elsewhere
		await page
			.locator( '#woocommerce-select-control-1__control-input' )
			.click( {
				force: true,
			} );
		await page
			.locator( '#woocommerce-select-control__option-1-no' )
			.click();
	},

	unselectBusinessFeatures: async ( page, expect_wc_pay = true ) => {
		await page.goto( BUSIENSS_DETAILS_URL );

		// Click the Free features tab
		await page.locator( '#tab-panel-0-business-features' ).click();
		const pageHeading = await page.textContent(
			'div.woocommerce-profile-wizard__step-header > h2'
		);
		expect( pageHeading ).toContain( 'Included business features' );
		// Expand list of features
		await page
			.locator(
				'button.woocommerce-admin__business-details__selective-extensions-bundle__expand'
			)
			.click();

		if ( expect_wc_pay ) {
			// Check to see if WC Payments is present
			const wcPay = await page.locator(
				'.woocommerce-admin__business-details__selective-extensions-bundle__description a[href*=woocommerce-payments]'
			);
			expect( wcPay ).toBeVisible();
		} else {
			// Make sure WC Payments is NOT present
			await expect(
				page.locator(
					'.woocommerce-admin__business-details__selective-extensions-bundle__description a[href*=woocommerce-payments]'
				)
			).toHaveCount( 0 );
		}
		// Uncheck all business features
		if ( page.isChecked( '.components-checkbox-control__input' ) ) {
			await page.locator( '.components-checkbox-control__input' ).click();
		}
	},
};

module.exports = onboarding;
