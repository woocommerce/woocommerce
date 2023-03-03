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
		await page.click( '#woocommerce-select-control-0__control-input' );
		await page.fill(
			'#woocommerce-select-control-0__control-input',
			store.country
		);
		await page.click( `button >> text=${ store.country }` );
		// Fill store's address - first line
		await page.fill( '#inspector-text-control-0', store.address );
		// Fill postcode of the store
		await page.fill( '#inspector-text-control-1', store.zip );
		// Fill the city where the store is located
		await page.fill( '#inspector-text-control-2', store.city );
		// Fill store's email address
		await page.fill( '#inspector-text-control-3', store.email );
		// Verify that checkbox next to "Get tips, product updates and inspiration straight to your mailbox" is selected
		await page.check( '#inspector-checkbox-control-0' );
		// Click continue button
		await page.click( 'button >> text=Continue' );
		// Usage tracking dialog
		await page.textContent( '.components-modal__header-heading' );
		await page.click( 'button >> text=No thanks' );
		await page.waitForLoadState( 'networkidle' ); // not autowaiting for form submission
	},

	completeIndustrySection: async ( page, industries, expectedIndustries ) => {
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
		expect( numCheckboxes ).toHaveLength( expectedIndustries );
		// Uncheck any currently checked industries
		for ( let i = 0; i < expectedIndustries; i++ ) {
			const currentCheck = `#inspector-checkbox-control-${ i }`;
			await page.uncheck( currentCheck );
		}

		for ( let industry of Object.values( industries ) ) {
			await page.click( `label >> text=${ industry }` );
		}
	},

	handleSaveChangesModal: async ( page, { saveChanges } ) => {
		// Save changes? Modal
		await page.textContent( '.components-modal__header-heading' );

		if ( saveChanges ) {
			await page.click( 'button >> text=Save' );
		} else {
			await page.click( 'button >> text=Discard' );
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
			await page.uncheck( currentCheck );
		}

		Object.keys( products ).forEach( async ( product ) => {
			await page.click( `label >> text=${ products[ product ] }` );
		} );
	},

	completeBusinessDetailsSection: async ( page ) => {
		await page.goto( BUSIENSS_DETAILS_URL );
		const pageHeading = await page.textContent(
			'div.woocommerce-profile-wizard__step-header > h2'
		);
		expect( pageHeading ).toContain( 'Tell us about your business' );
		// Select 1 - 10 for products
		await page.click( '#woocommerce-select-control-0__control-input', {
			force: true,
		} );
		await page.click( '#woocommerce-select-control__option-0-1-10' );
		// Select No for selling elsewhere
		await page.click( '#woocommerce-select-control-1__control-input', {
			force: true,
		} );
		await page.click( '#woocommerce-select-control__option-1-no' );
	},

	unselectBusinessFeatures: async ( page, expect_wc_pay = true ) => {
		await page.goto( BUSIENSS_DETAILS_URL );

		// Click the Free features tab
		await page.click( '#tab-panel-0-business-features' );
		const pageHeading = await page.textContent(
			'div.woocommerce-profile-wizard__step-header > h2'
		);
		expect( pageHeading ).toContain( 'Included business features' );
		// Expand list of features
		await page.click(
			'button.woocommerce-admin__business-details__selective-extensions-bundle__expand'
		);

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
			await page.click( '.components-checkbox-control__input' );
		}
	},
};

module.exports = onboarding;
