const { test, expect, request, Page } = require( '@playwright/test' );
const { tags } = require( '../../fixtures/fixtures' );
const { setOption, deleteOption } = require( '../../utils/options' );
const { ADMIN_STATE_PATH } = require( '../../playwright.config' );

/**
 * @type {Page}
 */
let page;

test.describe(
	'Analytics Settings - Scheduled Import',
	{ tag: [ tags.PAYMENTS, tags.SERVICES ] },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		test.beforeAll( async ( { browser } ) => {
			page = await browser.newPage();
		} );

		test.beforeEach( async () => {
			await test.step( `Go to Analytics > Settings`, async () => {
				await page.goto(
					'wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Fsettings'
				);
			} );
		} );

		test.afterAll( async () => {
			await page.close();
		} );

		test( 'should show Immediate mode by default when option is not set', async ( {
			baseURL,
		} ) => {
			// Delete the option to simulate a new installation
			await deleteOption(
				request,
				baseURL,
				'woocommerce_analytics_scheduled_import'
			);

			// Reload the page
			await page.reload();

			// Verify "Immediately" is selected
			await expect(
				page.getByRole( 'radio', {
					name: /Immediately/i,
				} )
			).toBeChecked();
		} );

		test( 'should switch from scheduled to immediate mode with confirmation modal - cancel flow', async ( {
			baseURL,
		} ) => {
			// Set to scheduled mode
			await setOption(
				request,
				baseURL,
				'woocommerce_analytics_scheduled_import',
				'yes'
			);

			// Reload the page
			await page.reload();

			// Verify "Scheduled (recommended)" is selected
			await expect(
				page.getByRole( 'radio', {
					name: /Scheduled \(recommended\)/i,
				} )
			).toBeChecked();

			// Click "Immediately" radio option
			await page.getByRole( 'radio', { name: /Immediately/i } ).click();

			// Verify confirmation modal appears
			await expect(
				page.getByRole( 'heading', { name: 'Are you sure?' } )
			).toBeVisible();

			// Click "Cancel" button
			await page
				.getByRole( 'button', { name: /Cancel/i, exact: false } )
				.click();

			// Verify modal closes
			await expect(
				page.locator(
					'.woocommerce-analytics-import-mode-confirmation-modal'
				)
			).toBeHidden();

			// Verify "Scheduled (recommended)" is still selected on page
			await expect(
				page.getByRole( 'radio', {
					name: /Scheduled \(recommended\)/i,
				} )
			).toBeChecked();
		} );

		test( 'should switch from scheduled to immediate mode with confirmation modal - confirm flow', async ( {
			baseURL,
		} ) => {
			// Set to scheduled mode
			await setOption(
				request,
				baseURL,
				'woocommerce_analytics_scheduled_import',
				'yes'
			);

			// Reload the page
			await page.reload();

			// Click "Immediately" radio option
			await page.getByRole( 'radio', { name: /Immediately/i } ).click();

			// Verify confirmation modal appears
			await expect(
				page.getByRole( 'heading', { name: 'Are you sure?' } )
			).toBeVisible();

			// Click "Confirm" button
			await page
				.getByRole( 'button', { name: /Confirm/i, exact: false } )
				.click();

			// Click "Save settings" button
			await page.getByRole( 'button', { name: 'Save settings' } ).click();

			// Verify success message
			await expect(
				page
					.getByText( 'Your settings have been successfully saved.' )
					.first()
			).toBeVisible();

			// Refresh page and verify "Immediately" is selected
			await page.reload();
			await expect(
				page.getByRole( 'radio', { name: /Immediately/i } )
			).toBeChecked();
		} );

		test( 'should switch from immediate to scheduled mode without confirmation modal', async ( {
			baseURL,
		} ) => {
			// Set to immediate mode
			await setOption(
				request,
				baseURL,
				'woocommerce_analytics_scheduled_import',
				'no'
			);

			// Reload the page
			await page.reload();

			// Verify "Immediately" is selected
			await expect(
				page.getByRole( 'radio', { name: /Immediately/i } )
			).toBeChecked();

			// Click "Scheduled (recommended)" radio option
			await page
				.getByRole( 'radio', {
					name: /Scheduled \(recommended\)/i,
				} )
				.click();

			// Verify NO modal appears (no warning when switching back to recommended mode)
			await expect(
				page.getByRole( 'heading', { name: 'Are you sure?' } )
			).toBeHidden();

			// Click "Save settings" button
			await page.getByRole( 'button', { name: 'Save settings' } ).click();

			// Verify success message
			await expect(
				page
					.getByText( 'Your settings have been successfully saved.' )
					.first()
			).toBeVisible();

			// Refresh page and verify "Scheduled (recommended)" is selected
			await page.reload();
			await expect(
				page.getByRole( 'radio', {
					name: /Scheduled \(recommended\)/i,
				} )
			).toBeChecked();
		} );
	}
);
