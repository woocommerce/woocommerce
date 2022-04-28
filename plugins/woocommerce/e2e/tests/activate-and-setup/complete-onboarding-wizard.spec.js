const { test, expect } = require( '@playwright/test' );

test.describe( 'Store owner can complete onboarding wizard', () => {
	test.use( { storageState: 'e2e/storage/adminState.json' } );

	test.beforeEach( async ( { page } ) => {
		// These tests all take place with a US store
		await page.goto(
			'wp-admin/admin.php?page=wc-admin&path=/setup-wizard'
		);
		// Fill store's address - first line
		await page.fill( '#inspector-text-control-0', 'addr 1' );
		// Fill store's address - second line
		await page.fill( '#inspector-text-control-1', 'addr 2' );
		// Type the requested country/region
		await page.click( '#woocommerce-select-control-0__control-input' );
		await page.fill(
			'#woocommerce-select-control-0__control-input',
			'United States (US) — California'
		);
		await page.click( 'button >> text=United States (US) — California' );
		// Fill the city where the store is located
		await page.fill( '#inspector-text-control-2', 'San Francisco' );
		// Fill postcode of the store
		await page.fill( '#inspector-text-control-3', '94107' );
		// Fill store's email address
		await page.fill(
			'#inspector-text-control-4',
			'admin@woocommercecoree2etestsuite.com'
		);
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

test.describe(
	'A japanese store can complete the selective bundle install but does not include WCPay.',
	() => {
		test.use( { storageState: 'e2e/storage/adminState.json' } );

		test.beforeEach( async ( { page } ) => {
			// These tests all take place with a store based in Japan
			await page.goto(
				'wp-admin/admin.php?page=wc-admin&path=/setup-wizard'
			);
			await page.fill( '#inspector-text-control-0', 'addr 1' );
			await page.fill( '#inspector-text-control-1', 'addr 2' );
			await page.click( '#woocommerce-select-control-0__control-input' );
			await page.fill(
				'#woocommerce-select-control-0__control-input',
				'Japan — Hokkaido'
			);
			await page.click( 'button >> text=Japan — Hokkaido' );
			await page.fill( '#inspector-text-control-2', 'Sapporo' );
			await page.fill( '#inspector-text-control-3', '007-0852' );
			await page.fill(
				'#inspector-text-control-4',
				'admin@woocommercecoree2etestsuite.com'
			);
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

		test( 'should display the choose payments task, and not the WC Pay task', async ( {
			page,
		} ) => {
			// Setup
			await page.goto(
				'wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard&step=theme'
			);
			await page.click( 'button >> text=Continue with my active theme' );
			// Start test
			// If the test is being retried, the modal may have already been dismissed
			await page.locator( '#adminmenumain' );
			const modalHeading = await page.$(
				'h2.woocommerce__welcome-modal__page-content__header'
			);
			if ( modalHeading ) {
				await expect( modalHeading ).toContain(
					'Welcome to your WooCommerce store’s online HQ!'
				);
				await page.click( '[aria-label="Close dialog"]' );
			}
			const listItem = await page.textContent(
				':nth-match(li[role=button], 3)'
			);
			expect( listItem ).toContain( 'Set up payments' );
			expect( listItem ).not.toContain( 'Set up WooCommerce Payments' );
		} );
	}
);

test.describe( 'Store owner can go through setup Task List', () => {
	test.use( { storageState: 'e2e/storage/adminState.json' } );

	test.beforeEach( async ( { page } ) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-admin&path=/setup-wizard'
		);
		await page.fill( '#inspector-text-control-0', 'addr 1' );
		await page.fill( '#inspector-text-control-1', 'addr 2' );
		await page.click( '#woocommerce-select-control-0__control-input' );
		await page.fill(
			'#woocommerce-select-control-0__control-input',
			'United States (US) — California'
		);
		await page.click( 'button >> text=United States (US) — California' );
		await page.fill( '#inspector-text-control-2', 'San Francisco' );
		await page.fill( '#inspector-text-control-3', '94107' );
		await page.fill(
			'#inspector-text-control-4',
			'admin@woocommercecoree2etestsuite.com'
		);
		await page.check( '#inspector-checkbox-control-0' );
		await page.click( 'button >> text=Continue' );
		await page.click( 'button >> text=No thanks' );
		await page.waitForLoadState( 'networkidle' ); // not autowaiting for form submission
	} );

	test( 'can setup shipping', async ( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=wc-admin' );
		// Close the welcome dialog if it's present
		await page.waitForLoadState( 'networkidle' ); // explictly wait because the welcome dialog loads last
		const welcomeDialog = await page.$( '.components-modal__header' );
		if ( welcomeDialog !== null ) {
			await page.click(
				'div.components-modal__header >> button.components-button'
			);
		}
		await expect( welcomeDialog ).not.toBeVisible();
		await page
			.locator( 'li[role="button"]:has-text("Set up shipping1 minute")' )
			.click();

		const shippingPage = await page.textContent( 'h1' );
		if ( shippingPage === 'Shipping' ) {
			// click the Add shipping zone button on the shipping settings page
			await page.locator( '.page-title-action' ).click();

			await expect(
				page.locator( 'h2', {
					hasText: 'Shipping zones',
				} )
			).toBeVisible();
		} else {
			await page.locator( 'button.components-button.is-primary' ).click();
		}
	} );
} );
