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

// these tests run in sequence and depend on the previous tests. They can't retry unfortunately.
// hopefully we can find a way to manage state between tests in the future
test.describe
	.serial( 'Store owner can skip the core profiler, proceed to setup', () => {
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

	test( 'Not taking action does not complete task on task list', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-admin' );
		// assert that the task list is shown
		await expect(
			page.getByRole( 'heading', {
				name: 'Start customizing your store',
			} )
		).toBeVisible();

		// click through to the first task, but don't change anything
		await test.step( 'Do not complete the first task', async () => {
			await page
				.getByRole( 'button', { name: 'Customize your store' } )
				.click();
			await page
				.locator( 'div' )
				.filter( { hasText: /^Customize your store$/ } )
				.getByRole( 'button' )
				.click();
		} );

		// assert that the task is not marked as complete
		await expect(
			page.getByRole( 'button', { name: 'Customize your store' } )
		).not.toHaveClass( 'complete' );

		await expect(
			page.getByRole( 'heading', {
				name: 'Start customizing your store',
			} )
		).toBeVisible();
	} );

	test( 'Taking action completes a task on the task list', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-admin' );

		// assert that the task list is shown
		await expect(
			page.getByRole( 'heading', {
				name: 'Start customizing your store',
			} )
		).toBeVisible();

		await test.step( 'Perform some actions to complete the first task', async () => {
			await page
				.getByRole( 'button', { name: 'Start customizing' } )
				.click();
			await page
				.getByRole( 'button', { name: 'Start designing' } )
				.click();
			await page
				.getByRole( 'button', { name: 'Design a new theme' } )
				.click();
		} );

		await test.step( 'Go back to the dashboard and confirm that the task is marked as complete', async () => {
			// hate doing this, but it's the only thing I could get to work
			await page.waitForTimeout( 12000 );
			await page
				.frameLocator( 'iframe[title="assembler-hub"]' )
				.getByLabel( 'Back' )
				.click();
			await page
				.frameLocator( 'iframe[title="assembler-hub"]' )
				.getByRole( 'button', { name: 'Exit and lose changes' } )
				.click();
			await page
				.locator( 'div' )
				.filter( { hasText: /^Customize your store$/ } )
				.getByRole( 'button' )
				.click();
		} );

		await expect(
			page.getByRole( 'heading', { name: 'List your products' } )
		).toBeVisible();
		await expect(
			page.getByRole( 'button', { name: 'Customize your store' } )
		).toHaveClass(
			'woocommerce-experimental-list__item has-action transitions-disabled woocommerce-task-list__item index-1 complete'
		);
	} );

	test( 'Complete the next step', async ( { page } ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-admin' );

		await test.step( 'Add your products', async () => {
			await page
				.getByRole( 'button', { name: 'Add your products' } )
				.click();
			await page
				.getByRole( 'menuitem', { name: 'Physical product' } )
				.click();
			await expect(
				page.getByRole( 'heading', { name: 'Add new product' } )
			).toBeVisible();
			await page.getByLabel( 'Product name' ).fill( 'Test Product' );
			await page
				.getByRole( 'button', { name: 'Publish', exact: true } )
				.click();

			await expect(
				page.getByText( 'Product published.' )
			).toBeVisible();

			await page.goto( 'wp-admin/admin.php?page=wc-admin' );

			await expect(
				page.getByRole( 'heading', { name: 'It’s time to get paid' } )
			).toBeVisible();
			await expect(
				page.getByRole( 'button', { name: 'Add your products' } )
			).toHaveClass(
				'woocommerce-experimental-list__item has-action transitions-disabled woocommerce-task-list__item index-2 complete'
			);
		} );
	} );

	test( 'Can hide the task list', async ( { page } ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-admin' );

		// assert that the task list is shown
		await expect(
			page.getByRole( 'heading', { name: 'It’s time to get paid' } )
		).toBeVisible();

		// hide the task list
		await page
			.getByRole( 'button', { name: 'Task List Options' } )
			.first()
			.click();
		await page.getByRole( 'button', { name: 'Hide setup list' } ).click();

		// assert that the task list is hidden
		await expect(
			page.getByRole( 'heading', {
				name: 'Start customizing your store',
			} )
		).not.toBeVisible();
	} );

	test( 'Store management displayed after task list complete/hidden', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-admin' );

		await expect(
			page.locator( 'div' ).filter( { hasText: /^Store management$/ } )
		).toBeVisible();
		await expect(
			page.getByText(
				'Marketing & MerchandisingMarketingAdd productsPersonalize my storeView my store'
			)
		).toBeVisible();
		await expect(
			page.getByText( 'SettingsStore detailsPaymentsTaxShipping' )
		).toBeVisible();
	} );

	test( 'Can access analytics reports from stats overview', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-admin' );

		await expect(
			page.locator( 'div' ).filter( { hasText: /^Stats overview$/ } )
		).toBeVisible();

		await page.getByRole( 'link', { name: 'View detailed stats' } ).click();

		await expect( page.url() ).toContain(
			'wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Foverview'
		);
	} );

	test( 'Extended task list visible', async ( { page } ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-admin' );

		await expect(
			page
				.locator( 'div' )
				.filter( { hasText: /^Things to do next1$/ } )
				.first()
		).toBeVisible();

		await page.getByRole( 'button', { name: 'Task List Options' } ).click();
		await page.getByRole( 'button', { name: 'Hide this' } ).click();

		await expect(
			page
				.locator( 'div' )
				.filter( { hasText: /^Things to do next1$/ } )
				.first()
		).not.toBeVisible();
	} );
} );
