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
			storeDetails.us.expectedNumberOfIndustries
		);
		await page.locator( 'button >> text=Continue' ).click();
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
			storeDetails.us.expectedNumberOfIndustries
		);

		// Navigate back to "Store Details" section
		await page.locator( 'button >> text=Store Details' ).click();
		await onboarding.handleSaveChangesModal( page, { saveChanges: true } );
		await page.locator( 'text="Welcome to WooCommerce"' ).waitFor();

		// Navigate back to "Industry" section
		await page.locator( 'button >> text=Industry' ).click();
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
			storeDetails.us.expectedNumberOfIndustries
		);

		// Navigate back to "Store Details" section
		await page.locator( 'button >> text=Store Details' ).click();

		await onboarding.handleSaveChangesModal( page, { saveChanges: false } );

		// Navigate back to "Industry" section
		await page.locator( 'button >> text=Industry' ).click();
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
		await page.locator( 'button >> text=Continue' ).click();
	} );

	// eslint-disable-next-line jest/expect-expect
	test( 'can complete the business section', async ( { page } ) => {
		// We have to ensure that previous steps are complete to avoid generating an error
		await onboarding.completeIndustrySection(
			page,
			storeDetails.us.industries,
			storeDetails.us.expectedNumberOfIndustries
		);
		await page.locator( 'button >> text=Continue' ).click();

		await onboarding.completeProductTypesSection(
			page,
			storeDetails.us.products
		);
		await page.locator( 'button >> text=Continue' ).click();

		await onboarding.completeBusinessDetailsSection( page );
		await page.locator( 'button >> text=Continue' ).click();
	} );

	// eslint-disable-next-line jest/expect-expect
	test.skip( 'can unselect all business features and continue', async ( {
		page,
	} ) => {
		// We have to ensure that previous steps are complete to avoid generating an error
		await onboarding.completeIndustrySection(
			page,
			storeDetails.us.industries,
			storeDetails.us.expectedNumberOfIndustries
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
		await page.locator( 'button >> text=Continue' ).click();
	} );
} );

// !Changed from Japanese to Liberian store, as Japanese Yen does not use decimals
test.describe(
	'A Liberian store can complete the selective bundle install but does not include WCPay.',
	() => {
		test.use( { storageState: process.env.ADMINSTATE } );

		test.beforeEach( async () => {
			// Complete "Store Details" step through the API to prevent flakiness when run on external sites.
			await api.update.storeDetails( storeDetails.liberia.store );
		} );

		// eslint-disable-next-line jest/expect-expect
		test( 'can choose the "Other" industry', async ( { page } ) => {
			await onboarding.completeIndustrySection(
				page,
				storeDetails.liberia.industries,
				storeDetails.liberia.expectedNumberOfIndustries
			);
			await page.locator( 'button >> text=Continue' ).click();
		} );

		// eslint-disable-next-line jest/expect-expect
		test( 'can choose not to install any extensions', async ( {
			page,
		} ) => {
			const expect_wp_pay = false;

			await onboarding.completeIndustrySection(
				page,
				storeDetails.liberia.industries,
				storeDetails.liberia.expectedNumberOfIndustries
			);
			await page.locator( 'button >> text=Continue' ).click();

			await onboarding.completeProductTypesSection(
				page,
				storeDetails.liberia.products
			);
			// Make sure WC Payments is NOT present
			await expect(
				page.locator(
					'.woocommerce-admin__business-details__selective-extensions-bundle__description a[href*=woocommerce-payments]'
				)
			).not.toBeVisible();

			await page.locator( 'button >> text=Continue' ).click();

			await onboarding.completeBusinessDetailsSection( page );
			await page.locator( 'button >> text=Continue' ).click();

			await onboarding.unselectBusinessFeatures( page, expect_wp_pay );

			await page.locator( 'button >> text=Continue' ).click();

			await expect( page ).not.toHaveURL( /.*step=business-details/ );
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
			await onboarding.completeBusinessDetailsSection( page );
			await page.locator( 'button >> text=Continue' ).click();

			await onboarding.unselectBusinessFeatures( page, expect_wp_pay );
			await page.locator( 'button >> text=Continue' ).click();

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
		await page
			.locator( '#woocommerce-select-control-0__control-input' )
			.click();
		await page
			.locator( '#woocommerce-select-control-0__control-input' )
			.fill( 'United States (US) — California' );
		await page
			.locator( 'button >> text=United States (US) — California' )
			.click();
		await page.locator( '#inspector-text-control-0' ).fill( 'addr 1' );
		await page.locator( '#inspector-text-control-1' ).fill( '94107' );
		await page
			.locator( '#inspector-text-control-2' )
			.fill( 'San Francisco' );
		await page
			.locator( '#inspector-text-control-3' )
			.fill( storeDetails.us.store.email );
		await page.locator( '#inspector-checkbox-control-0' ).check();
		await page.locator( 'button >> text=Continue' ).click();
		await page.locator( 'button >> text=No thanks' ).click();
		await page.locator( 'button >> text=Continue' ).click();
		await page.locator( 'button >> text=Continue' ).click();
		await page.locator( 'button >> text=Continue' ).click();
		// Uncheck all business features
		if (
			page.locator( '.components-checkbox-control__input' ).isChecked()
		) {
			await page.locator( '.components-checkbox-control__input' ).click();
		}
		await page.locator( 'button >> text=Continue' ).click();
		await page.waitForLoadState( 'networkidle' ); // not autowaiting for form submission
	} );

	test( 'can setup shipping', async ( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=wc-admin' );
		await page.locator( 'div >> text=Review Shipping Options' ).click();

		// dismiss tourkit if visible
		const tourkitVisible = await page
			.locator( 'button.woocommerce-tour-kit-step-controls__close-btn' )
			.isVisible();
		if ( tourkitVisible ) {
			await page
				.locator(
					'button.woocommerce-tour-kit-step-controls__close-btn'
				)
				.click();
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
