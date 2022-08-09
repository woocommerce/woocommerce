const { test, expect } = require( '@playwright/test' );

const adminEmail =
	process.env.USE_WP_ENV === '1'
		? 'wordpress@example.com'
		: 'admin@woocommercecoree2etestsuite.com';

test.describe( 'Store owner can complete onboarding wizard', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeEach( async ( { page } ) => {
		// These tests all take place with a US store
		await page.goto(
			'wp-admin/admin.php?page=wc-admin&path=/setup-wizard'
		);
		// Type the requested country/region
		await page.click( '#woocommerce-select-control-0__control-input' );
		await page.fill(
			'#woocommerce-select-control-0__control-input',
			'United States (US) — California'
		);
		await page.click( 'button >> text=United States (US) — California' );
		// Fill store's address - first line
		await page.fill( '#inspector-text-control-0', 'addr 1' );
		// Fill postcode of the store
		await page.fill( '#inspector-text-control-1', '94107' );
		// Fill the city where the store is located
		await page.fill( '#inspector-text-control-2', 'San Francisco' );
		// Fill store's email address
		await page.fill( '#inspector-text-control-3', adminEmail );
		// Verify that checkbox next to "Get tips, product updates and inspiration straight to your mailbox" is selected
		await page.check( '#inspector-checkbox-control-0' );
		// Click continue button
		await page.click( 'button >> text=Continue' );
		// Usage tracking dialog
		await page.textContent( '.components-modal__header-heading' );
		await page.click( 'button >> text=No thanks' );
		await page.waitForLoadState( 'networkidle' ); // not autowaiting for form submission
	} );

	test( 'can complete the industry section', async ( { page } ) => {
		// There are 8 checkboxes on the page (in the US), adjust this constant if we change that
		const expectedIndustries = 8;
		await page.goto(
			'wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard&step=industry'
		);
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
		expect( numCheckboxes.length === expectedIndustries ).toBeTruthy();
		// Uncheck any currently checked industries
		for ( let i = 0; i < expectedIndustries; i++ ) {
			const currentCheck = `#inspector-checkbox-control-${ i }`;
			await page.uncheck( currentCheck );
		}
		// Check the fashion and health & beauty industries
		await page.check( 'label >> text=Fashion, apparel, and accessories' );
		await page.check( 'label >> text=Health and beauty' );
		await page.click( 'button >> text=Continue' );
	} );

	test( 'can complete the product types section', async ( { page } ) => {
		// There are 7 checkboxes on the page, adjust this constant if we change that
		const expectedProductTypes = 7;
		await page.goto(
			'wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard&step=product-types'
		);
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
		expect( numCheckboxes.length === expectedProductTypes ).toBeTruthy();
		// Uncheck any currently checked products
		for ( let i = 0; i < expectedProductTypes; i++ ) {
			const currentCheck = `#inspector-checkbox-control-${ i }`;
			await page.uncheck( currentCheck );
		}
		// Check the Physical and Downloadable products
		await page.check( 'label >> text=Physical products' );
		await page.check( 'label >> text=Downloads' );
		await page.click( 'button >> text=Continue' );
	} );

	test( 'can complete the business section', async ( { page } ) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard&step=business-details'
		);
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
		await page.click( 'button >> text=Continue' );
	} );

	test( 'can unselect all business features and continue', async ( {
		page,
	} ) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard&step=business-details'
		);
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
		// Check to see if WC Payments is present
		const wcPay = await page.locator(
			'a:has-text("WooCommerce Payments")'
		);
		expect( wcPay ).toBeVisible();
		// Uncheck all business features
		if ( page.isChecked( '#inspector-checkbox-control-1' ) ) {
			await page.click( '#inspector-checkbox-control-1' );
		}
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

		test.beforeEach( async ( { page } ) => {
			// These tests all take place with a store based in Japan
			await page.goto(
				'wp-admin/admin.php?page=wc-admin&path=/setup-wizard'
			);
			await page.click( '#woocommerce-select-control-0__control-input' );
			await page.fill(
				'#woocommerce-select-control-0__control-input',
				'Malta'
			);
			await page.click( 'button >> text=Malta' );
			await page.fill( '#inspector-text-control-0', 'addr 1' );
			await page.fill( '#inspector-text-control-1', 'VLT 1011' );
			await page.fill( '#inspector-text-control-2', 'Valletta' );
			await page.fill( '#inspector-text-control-3', adminEmail );
			await page.check( '#inspector-checkbox-control-0' );
			await page.click( 'button >> text=Continue' );
			await page.click( 'button >> text=No thanks' );
			await page.waitForLoadState( 'networkidle' ); // not autowaiting for form submission
		} );

		test( 'can choose the "Other" industry', async ( { page } ) => {
			const expectedIndustries = 7;
			await page.goto(
				'wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard&step=industry'
			);
			const pageHeading = await page.textContent(
				'div.woocommerce-profile-wizard__step-header > h2'
			);
			expect( pageHeading ).toContain(
				'In which industry does the store operate?'
			);
			// Uncheck all industries
			for ( let i = 0; i < expectedIndustries; i++ ) {
				const currentCheck = `#inspector-checkbox-control-${ i }`;
				await page.uncheck( currentCheck );
			}
			// Check Other for industry
			await page.check( 'label >> text="Other"' );
			await page.click( 'button >> text=Continue' );
		} );

		test( 'can choose not to install any extensions', async ( {
			page,
		} ) => {
			await page.goto(
				'wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard&step=business-details'
			);
			// Click the Free features tab
			await page.click( '#tab-panel-0-business-features' );
			const pageHeading = await page.textContent(
				'div.woocommerce-profile-wizard__step-header > h2'
			);
			await expect( pageHeading ).toContain(
				'Included business features'
			);
			// Expand list of features
			await page.click(
				'button.woocommerce-admin__business-details__selective-extensions-bundle__expand'
			);
			// Make sure WC Payments is NOT present
			await expect(
				page.locator( 'a:has-text("WooCommerce Payments")' )
			).toHaveCount( 0 );
			// Uncheck all business features
			if ( page.isChecked( '#inspector-checkbox-control-1' ) ) {
				await page.click( '#inspector-checkbox-control-1' );
			}
			await page.click( 'button >> text=Continue' );
		} );

		// Skipping this test because it's very flaky.  Onboarding checklist changed so that the text
		// changes when a task is completed.
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

test.describe( 'Store owner can go through setup Task List', () => {
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
		await page.fill( '#inspector-text-control-3', adminEmail );
		await page.check( '#inspector-checkbox-control-0' );
		await page.click( 'button >> text=Continue' );
		await page.click( 'button >> text=No thanks' );
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
