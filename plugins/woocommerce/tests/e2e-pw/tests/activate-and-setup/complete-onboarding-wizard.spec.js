const { test, expect } = require( '@playwright/test' );
const { onboarding } = require( '../../utils' );
const { storeDetails } = require( '../../test-data/data' );
const { api } = require( '../../utils' );

test.describe( 'Store owner can complete onboarding wizard', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeEach( async () => {
		// Complete "Store Details" step through the API to prevent flakiness when run on external sites.
		await api.update.storeDetails( storeDetails.us.store );
	} );

	// eslint-disable-next-line jest/expect-expect
	test( 'can complete the "Store Details" section', async ( { page } ) => {
		await onboarding.completeStoreDetailsSection(
			page,
			storeDetails.us.store
		);
	} );

	// eslint-disable-next-line jest/expect-expect
	test( 'can complete the industry section', async ( { page } ) => {
		await onboarding.completeIndustrySection(
			page,
			storeDetails.us.industries,
			storeDetails.us.expectedIndustries
		);
		await page.click( 'button >> text=Continue' );
		await expect( page ).toHaveURL( /.*step=product-types/ );
		await expect(
			page.locator( '.product-types button >> text=Continue' )
		).toBeVisible();
	} );

	// eslint-disable-next-line jest/expect-expect
	test( 'can save industry changes when navigating back to "Store Details"', async ( {
		page,
	} ) => {
		await onboarding.completeIndustrySection(
			page,
			storeDetails.us.industries2,
			storeDetails.us.expectedIndustries
		);

		// Navigate back to "Store Details" section
		await page.click( 'button >> text=Store Details' );
		await onboarding.handleSaveChangesModal( page, { saveChanges: true } );
		await page.locator( 'text="Welcome to WooCommerce"' ).waitFor();

		// Navigate back to "Industry" section
		await page.click( 'button >> text=Industry' );
		await page.textContent( '.components-checkbox-control__input' );
		for ( let industry of Object.values( storeDetails.us.industries2 ) ) {
			await expect( page.getByLabel( industry ) ).toBeChecked();
		}
	} );

	// eslint-disable-next-line jest/expect-expect
	test( 'can discard industry changes when navigating back to "Store Details"', async ( {
		page,
	} ) => {
		await onboarding.completeIndustrySection(
			page,
			storeDetails.us.industries,
			storeDetails.us.expectedIndustries
		);

		// Navigate back to "Store Details" section
		await page.click( 'button >> text=Store Details' );

		await onboarding.handleSaveChangesModal( page, { saveChanges: false } );

		// Navigate back to "Industry" section
		await page.click( 'button >> text=Industry' );
		await page.textContent( '.components-checkbox-control__input' );
		for ( let industry of Object.values( storeDetails.us.industries2 ) ) {
			await expect( page.getByLabel( industry ) ).toBeChecked();
		}
	} );

	// eslint-disable-next-line jest/expect-expect
	test( 'can complete the product types section', async ( { page } ) => {
		await onboarding.completeProductTypesSection(
			page,
			storeDetails.us.products
		);
		await page.click( 'button >> text=Continue' );
	} );

	// eslint-disable-next-line jest/expect-expect
	test( 'can complete the business section', async ( { page } ) => {
		// We have to ensure that previous steps are complete to avoid generating an error
		await onboarding.completeIndustrySection(
			page,
			storeDetails.us.industries,
			storeDetails.us.expectedIndustries
		);
		await page.click( 'button >> text=Continue' );

		await onboarding.completeProductTypesSection(
			page,
			storeDetails.us.products
		);
		await page.click( 'button >> text=Continue' );

		await onboarding.completeBusinessDetailsSection( page );
		await page.click( 'button >> text=Continue' );
	} );

	// eslint-disable-next-line jest/expect-expect
	test.skip( 'can unselect all business features and continue', async ( {
		page,
	} ) => {
		// We have to ensure that previous steps are complete to avoid generating an error
		await onboarding.completeIndustrySection(
			page,
			storeDetails.us.industries,
			storeDetails.us.expectedIndustries
		);
		// Check to see if WC Payments is present
		const wcPay = await page.locator(
			'.woocommerce-admin__business-details__selective-extensions-bundle__description a[href*=woocommerce-payments]'
		);

		await onboarding.completeProductTypesSection(
			page,
			storeDetails.us.products
		);

		await onboarding.unselectBusinessFeatures( page );
		await page.click( 'button >> text=Continue' );
	} );

	test( 'can complete the theme selection section', async ( { page } ) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard&step=theme'
		);
		const pageHeading = await page.textContent(
			'div.woocommerce-profile-wizard__step-header > h2'
		);
		expect( pageHeading ).toContain( 'Choose a theme' );
		// Just continue with the current theme
		await page.click( 'button >> text=Continue with my active theme' );
	} );
} );

// !Changed from Japanese to Malta store, as Japanese Yen does not use decimals
test.describe(
	'A Malta store can complete the selective bundle install but does not include WCPay.',
	() => {
		test.use( { storageState: process.env.ADMINSTATE } );

		test.beforeEach( async () => {
			// Complete "Store Details" step through the API to prevent flakiness when run on external sites.
			await api.update.storeDetails( storeDetails.malta.store );
		} );

		// eslint-disable-next-line jest/expect-expect
		test( 'can choose the "Other" industry', async ( { page } ) => {
			await onboarding.completeIndustrySection(
				page,
				storeDetails.malta.industries,
				storeDetails.malta.expectedIndustries
			);
			await page.click( 'button >> text=Continue' );
		} );

		// eslint-disable-next-line jest/expect-expect
		test( 'can choose not to install any extensions', async ( {
			page,
		} ) => {
			const expect_wp_pay = false;

			await onboarding.completeIndustrySection(
				page,
				storeDetails.malta.industries,
				storeDetails.malta.expectedIndustries
			);
			await page.click( 'button >> text=Continue' );

			await onboarding.completeProductTypesSection(
				page,
				storeDetails.malta.products
			);
			// Make sure WC Payments is NOT present
			await expect(
				page.locator(
					'.woocommerce-admin__business-details__selective-extensions-bundle__description a[href*=woocommerce-payments]'
				)
			).toHaveCount( 0 );

			await page.click( 'button >> text=Continue' );

			await onboarding.completeBusinessDetailsSection( page );
			await page.click( 'button >> text=Continue' );

			await onboarding.unselectBusinessFeatures( page, expect_wp_pay );

			await page.click( 'button >> text=Continue' );
		} );

		// Skipping this test because it's very flaky.  Onboarding checklist changed so that the text
		// changes when a task is completed.
		// eslint-disable-next-line jest/no-disabled-tests
		test.skip( 'should display the choose payments task, and not the WC Pay task', async ( {
			page,
		} ) => {
			// If payment has previously been setup, the setup checklist will show something different
			// This step resets it
			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=checkout'
			);
			// Ensure that all payment methods are disabled
			await expect(
				page.locator( '.woocommerce-input-toggle--disabled' )
			).toHaveCount( 3 );
			// Checklist shows when completing setup wizard
			await page.goto(
				'wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard&step=theme'
			);
			await page.click( 'button >> text=Continue with my active theme' );
			// Start test
			await page.waitForLoadState( 'networkidle' );
			await expect(
				page.locator(
					':nth-match(.woocommerce-task-list__item-title, 3)'
				)
			).toContainText( 'Set up payments' );
			await expect(
				page.locator(
					':nth-match(.woocommerce-task-list__item-title, 3)'
				)
			).not.toContainText( 'Set up WooCommerce Payments' );
		} );
	}
);

// Skipping this test because it's very flaky.
test.describe.skip( 'Store owner can go through setup Task List', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeEach( async ( { page } ) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-admin&path=/setup-wizard'
		);
		await page.click( '#woocommerce-select-control-0__control-input' );
		await page.fill(
			'#woocommerce-select-control-0__control-input',
			'United States (US) — California'
		);
		await page.click( 'button >> text=United States (US) — California' );
		await page.fill( '#inspector-text-control-0', 'addr 1' );
		await page.fill( '#inspector-text-control-1', '94107' );
		await page.fill( '#inspector-text-control-2', 'San Francisco' );
		await page.fill(
			'#inspector-text-control-3',
			storeDetails.us.store.email
		);
		await page.check( '#inspector-checkbox-control-0' );
		await page.click( 'button >> text=Continue' );
		await page.click( 'button >> text=No thanks' );
		await page.click( 'button >> text=Continue' );
		await page.click( 'button >> text=Continue' );
		await page.click( 'button >> text=Continue' );
		// Uncheck all business features
		if ( page.isChecked( '.components-checkbox-control__input' ) ) {
			await page.click( '.components-checkbox-control__input' );
		}
		await page.click( 'button >> text=Continue' );
		await page.click( 'button >> text=Continue with my active theme' );
		await page.waitForLoadState( 'networkidle' ); // not autowaiting for form submission
	} );

	test( 'can setup shipping', async ( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=wc-admin' );
		await page.click( 'div >> text=Review Shipping Options' );

		// dismiss tourkit if visible
		const tourkitVisible = await page
			.locator( 'button.woocommerce-tour-kit-step-controls__close-btn' )
			.isVisible();
		if ( tourkitVisible ) {
			await page.click(
				'button.woocommerce-tour-kit-step-controls__close-btn'
			);
		}

		// check for automatically added shipping zone
		await expect(
			page.locator( 'tr[data-id="1"] >> td.wc-shipping-zone-name > a' )
		).toContainText( 'United States (US)' );
		await expect(
			page.locator( 'tr[data-id="1"] >> td.wc-shipping-zone-region' )
		).toContainText( 'United States (US)' );
		await expect(
			page.locator(
				'tr[data-id="1"] >> td.wc-shipping-zone-methods > div > ul > li'
			)
		).toContainText( 'Free shipping' );
	} );
} );
