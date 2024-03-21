const { test, expect } = require( '@playwright/test' );

test.describe( 'Store owner can complete the core profiler', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test( 'Can complete the core profiler skipping extension install', async ( {
		page,
	} ) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard'
		);

		await test.step( 'Intro page and opt in to data sharing', async () => {
			await expect(
				page.getByRole( 'heading', { name: 'Welcome to Woo!' } )
			).toBeVisible();
			await page
				.getByRole( 'checkbox', { name: 'I agree to share my data' } )
				.check();
			await page
				.getByRole( 'button', { name: 'Set up my store' } )
				.click();
		} );

		await test.step( 'User profile information', async () => {
			await expect(
				page.getByRole( 'heading', {
					name: 'Which one of these best describes you?',
				} )
			).toBeVisible();
			await page
				.getByRole( 'radio', { name: "I'm just starting my business" } )
				.first()
				.click();
			await page.getByRole( 'button', { name: 'Continue' } ).click();
		} );

		await test.step( 'Business Information', async () => {
			await expect(
				page.getByRole( 'heading', {
					name: 'Tell us a bit about your store',
				} )
			).toBeVisible();
			await expect(
				page.getByPlaceholder( 'Ex. My awesome store' )
			).toHaveValue( 'WooCommerce Core E2E Test Suite' );
			await page
				.locator(
					'form.woocommerce-profiler-business-information-form > div > div > div > div > input'
				)
				.first()
				.click();
			// select clothing and accessories
			await page
				.getByRole( 'option', { name: 'Clothing and accessories' } )
				.click();
			// select a WooPayments compatible location
			await page
				.locator(
					'form.woocommerce-profiler-business-information-form > div > div > div > div > input'
				)
				.last()
				.click();
			await page
				.getByRole( 'option', {
					name: 'Australia — Northern Territory',
				} )
				.click();

			await page
				.getByPlaceholder( 'wordpress@example.com' )
				.fill( 'merchant@example.com' );
			await page.getByLabel( 'Opt-in to receive tips,' ).uncheck();
			await page.getByRole( 'button', { name: 'Continue' } ).click();
		} );

		await test.step( 'Extensions -- do not install any', async () => {
			await expect(
				page.getByRole( 'heading', {
					name: 'Get a boost with our free features',
				} )
			).toBeVisible();
			// check that WooPayments is displayed because Australia is a supported country
			await expect(
				page.getByRole( 'heading', {
					name: 'Get paid with WooPayments',
				} )
			).toBeVisible();
			// skip this step so that no extensions are installed
			await page
				.getByRole( 'button', { name: 'Skip this step' } )
				.click();
		} );

		await test.step( 'Confirm that core profiler was completed and no extensions installed', async () => {
			// intermediate page shown
			await expect(
				page.getByRole( 'heading', { name: 'Turning on the lights' } )
			).toBeVisible();
			await expect(
				page.locator( '.woocommerce-onboarding-progress-bar__filler' )
			).toBeVisible();
			// dashboard shown
			await expect(
				page.getByRole( 'heading', {
					name: 'Welcome to WooCommerce Core E2E Test Suite',
				} )
			).toBeVisible();
			await expect(
				page.getByText( 'List your products' )
			).toBeVisible();
			// go to the plugins page to make sure that extensions weren't installed
			await page.goto( 'wp-admin/plugins.php' );
			await expect(
				page.getByRole( 'heading', { name: 'Plugins', exact: true } )
			).toBeVisible();
			// confirm that some of the optional extensions aren't present
			await expect( page.getByText( 'MailPoet' ) ).not.toBeAttached();
			await expect( page.getByText( 'Pinterest' ) ).not.toBeAttached();
			await expect(
				page.getByText( 'Google Listings & Ads' )
			).not.toBeAttached();
		} );

		await test.step( 'Confirm that information from core profiler saved', async () => {
			await page.goto( 'wp-admin/admin.php?page=wc-settings' );
			await expect(
				page.getByRole( 'textbox', {
					name: 'Australia — Northern Territory',
				} )
			).toBeVisible();
			await expect(
				page.getByRole( 'textbox', { name: 'Australian dollar ($)' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'textbox', { name: 'Left' } )
			).toBeVisible();
			await expect(
				page.getByLabel( 'Thousand separator', { exact: true } )
			).toHaveValue( ',' );
			await expect(
				page.getByLabel( 'Decimal separator', { exact: true } )
			).toHaveValue( '.' );
			await expect( page.getByLabel( 'Number of decimals' ) ).toHaveValue(
				'2'
			);
		} );
	} );

	test( 'Can complete the core profiler installing default extensions', async ( {
		page,
	} ) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard'
		);

		await test.step( 'Intro page and opt in to data sharing', async () => {
			await expect(
				page.getByRole( 'heading', { name: 'Welcome to Woo!' } )
			).toBeVisible();
			await page
				.getByRole( 'checkbox', { name: 'I agree to share my data' } )
				.check();
			await page
				.getByRole( 'button', { name: 'Set up my store' } )
				.click();
		} );

		await test.step( 'User profile information', async () => {
			await expect(
				page.getByRole( 'heading', {
					name: 'Which one of these best describes you?',
				} )
			).toBeVisible();
			await page
				.getByRole( 'radio', { name: "I'm already selling" } )
				.first()
				.click();
			await page.getByLabel( 'Select an option' ).click();
			await page
				.getByRole( 'option', { name: "No, I'm selling offline" } )
				.click();
			await page.getByRole( 'button', { name: 'Continue' } ).click();
		} );

		await test.step( 'Business Information', async () => {
			await expect(
				page.getByRole( 'heading', {
					name: 'Tell us a bit about your store',
				} )
			).toBeVisible();
			await expect(
				page.getByPlaceholder( 'Ex. My awesome store' )
			).toHaveValue( 'WooCommerce Core E2E Test Suite' );
			await page
				.locator(
					'form.woocommerce-profiler-business-information-form > div > div > div > div > input'
				)
				.first()
				.click();
			// select food and drink
			await page
				.getByRole( 'option', { name: 'Food and drink' } )
				.click();
			// select a WooPayments incompatible location
			await page
				.locator(
					'form.woocommerce-profiler-business-information-form > div > div > div > div > input'
				)
				.last()
				.click();
			await page.getByRole( 'option', { name: 'Afghanistan' } ).click();

			await page
				.getByPlaceholder( 'wordpress@example.com' )
				.fill( 'merchant@example.com' );
			await page.getByLabel( 'Opt-in to receive tips,' ).uncheck();
			await page.getByRole( 'button', { name: 'Continue' } ).click();
		} );

		await test.step( 'Extensions -- install some suggested extensions', async () => {
			await expect(
				page.getByRole( 'heading', {
					name: 'Get a boost with our free features',
				} )
			).toBeVisible();
			// check that WooPayments is not displayed because Afghanistan is not a supported country
			await expect(
				page.getByRole( 'heading', {
					name: 'Get paid with WooPayments',
				} )
			).not.toBeAttached();
			// select and install the rest of the extentions
			await page.locator( '#inspector-checkbox-control-2' ).uncheck();
			await page.locator( '#inspector-checkbox-control-3' ).check();
			await page.locator( '#inspector-checkbox-control-4' ).uncheck();
			await page.locator( '#inspector-checkbox-control-5' ).check();
			await page.getByRole( 'button', { name: 'Continue' } ).click();
		} );

		await test.step( 'Confirm that core profiler was completed and a couple of default extensions installed', async () => {
			page.on( 'dialog', ( dialog ) => dialog.accept() );
			// intermediate page shown
			await expect(
				page.getByRole( 'heading', {
					name: "Woo! Let's get your features ready",
				} )
			).toBeVisible( { timeout: 30000 } );
			await expect(
				page.getByRole( 'heading', {
					name: "Extending your store's capabilities",
				} )
			).toBeVisible( { timeout: 30000 } );
			// dashboard shown
			await expect(
				page.getByRole( 'heading', {
					name: 'Welcome to WooCommerce Core E2E Test Suite',
				} )
			).toBeVisible();
			await expect(
				page.getByText( 'List your products' )
			).toBeVisible();
			// go to the plugins page to make sure that extensions were installed
			await page.goto( 'wp-admin/plugins.php' );
			await expect(
				page.getByRole( 'heading', { name: 'Plugins', exact: true } )
			).toBeVisible();
			// confirm that the optional plugins are present
			await expect(
				page.getByText( 'Pinterest for WooCommerce', { exact: true } )
			).toBeVisible();
			await expect(
				page.getByText( 'Google Listings and Ads', { exact: true } )
			).toBeVisible();
		} );

		await test.step( 'Confirm that information from core profiler saved', async () => {
			await page.goto( 'wp-admin/admin.php?page=wc-settings' );
			await expect(
				page.getByRole( 'textbox', { name: 'Afghanistan' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'textbox', { name: 'Afghan afghani (؋)' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'textbox', { name: 'Left with space' } )
			).toBeVisible();
			await expect(
				page.getByLabel( 'Thousand separator', { exact: true } )
			).toHaveValue( '.' );
			await expect(
				page.getByLabel( 'Decimal separator', { exact: true } )
			).toHaveValue( ',' );
			await expect( page.getByLabel( 'Number of decimals' ) ).toHaveValue(
				'0'
			);
		} );

		await test.step( 'Clean up installed extensions', async () => {
			await page.goto( 'wp-admin/plugins.php' );
			await page.getByLabel( 'Deactivate Google Listings' ).click();
			await expect(
				page.getByText( 'Plugin deactivated.' )
			).toBeVisible();
			await page.getByLabel( 'Delete Google Listings' ).click();
			await expect(
				page.getByText(
					'Google Listings and Ads was successfully deleted.'
				)
			).toBeVisible();
			await page.getByLabel( 'Deactivate Pinterest for' ).click();
			await expect(
				page.getByText( 'Plugin deactivated.' )
			).toBeVisible();
			await page.getByLabel( 'Delete Pinterest for' ).click();
			await expect(
				page.getByText(
					'Pinterest for WooCommerce was successfully deleted.'
				)
			).toBeVisible();
		} );
	} );
} );

test.describe( 'Store owner can skip the core profiler', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test( 'Can click skip guided setup', async ( { page } ) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard'
		);

		await page.getByRole( 'button', { name: 'Skip guided setup' } ).click();

		await expect(
			page.getByRole( 'heading', {
				name: 'Where is your business located?',
			} )
		).toBeVisible();
		await page.getByLabel( 'Select country/region' ).click();
		await page
			.getByRole( 'option', { name: 'United States (US) — California' } )
			.click();
		await page.getByRole( 'button', { name: 'Go to my store' } ).click();

		await expect(
			page.getByRole( 'heading', { name: 'Turning on the lights' } )
		).toBeVisible();

		await expect(
			page.getByRole( 'heading', {
				name: 'Welcome to WooCommerce Core E2E Test Suite',
			} )
		).toBeVisible();
	} );
} );
