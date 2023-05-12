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
		//await page.click( 'button >> text=Continue' );//translate
		// await page.click( 'button >> text=Seguir' );//translate
		await page.click( 'button >> text=متابعة' );//translate
		// Usage tracking dialog
		await page.textContent( '.components-modal__header-heading' );
		//await page.click( 'button >> text=No thanks' );//translate
		//await page.click( 'button >> text=No, gracias' );//translate
		await page.click( 'button >> text=لا شكرًا' );//translate
		await page.waitForLoadState( 'networkidle' ); // not autowaiting for form submission
	},

	completeIndustrySection: async ( page, industries, expectedNumberOfIndustries ) => {
		await page.goto( INDUSTRY_DETAILS_URL );
		const pageHeading = await page.textContent(
			'div.woocommerce-profile-wizard__step-header > h2'
		);

		// expect( pageHeading ).toContain(
		// 	'In which industry does the store operate?'
		// );//translate
		// expect( pageHeading ).toContain(
		// 	'¿En qué sector opera la tienda?'
		// );//translate
		expect( pageHeading ).toContain(
			'في أي مجال يعمل المتجر؟'
		);//translate

	
		// Check that there are the correct number of options listed
		const numCheckboxes = await page.$$(
			'.components-checkbox-control__input'
		);
		expect( numCheckboxes ).toHaveLength( expectedNumberOfIndustries );
		// Uncheck any currently checked industries
		for ( let i = 0; i < expectedNumberOfIndustries; i++ ) {
			const currentCheck = `#inspector-checkbox-control-${ i }`;
			await page.uncheck( currentCheck );
		}

		const testVar = 'الأزياء والملابس والإكسسوارات';
		await page.getByText(`${testVar}`).click();

		const fashionAlone ='الأزياء';		

		const testStoreDetails = {
			us: {
				store: {
					address: 'addr1',
					city: 'San Francisco',
					zip: '94107',
					//country: 'United States (US) — California', // corresponding to the text value of the option,//translate
					//country: 'Estados Unidos (EEUU) — California', // corresponding to the text value of the option,//translate
					country: 'الولايات المتحدة الأمريكية — كاليفورنيا',//translate
					countryCode: 'US:CA',
				},
				expectedNumberOfIndustries: 8, // There are 8 checkboxes on the page (in the US), adjust this constant if we change that
				industries: {
					testfashion:'الأزياء'
				}
			}
		};

		await page.getByText(`${testVar}`).click();

		console.log('testVar=',testVar);

		// await page.getByText(`/${fashionAlone}/`).click();//failed

		await page.getByText(fashionAlone).click();
		
		await page.getByText(testStoreDetails.us.industries.testfashion).click();
		console.log('testStoreDetails.us.industries.testfashion',testStoreDetails.us.industries.testfashion);

		await page.getByText(`${testStoreDetails.us.industries.testfashion}`).click();


		for ( let industry of Object.values( industries ) ) {
			console.log('industry=',industry);
			//await page.click( `label >> text=${ industry }` );
			//await page.getByText(industry).click();
			await page.getByText(`${industry}`).click();
		}
	},

	handleSaveChangesModal: async ( page, { saveChanges } ) => {
		// Save changes? Modal
		await page.textContent( '.components-modal__header-heading' );

		if ( saveChanges ) {
			//await page.click( 'button >> text=Save' );//translate
			await page.click( 'button >> text=Guardar' );//translate
			
		} else {
			//await page.click( 'button >> text=Discard' );//translate
			await page.click( 'button >> text=Descartar' );//translate
			
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
		// expect( pageHeading ).toContain(
		// 	'What type of products will be listed?'
		// );//translate
		expect( pageHeading ).toContain(
			'¿Qué tipo de productos se mostrarán?'
		);//translate
		
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
		//expect( pageHeading ).toContain( 'Tell us about your business' );//translate
		expect( pageHeading ).toContain( 'Háblanos de tu negocio' );//translate
		
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
		//expect( pageHeading ).toContain( 'Included business features' );//translate
		expect( pageHeading ).toContain( 'Características de negocio incluidas' );//translate
		
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
