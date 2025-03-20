const { test, expect, request } = require( '@playwright/test' );
const { setOption } = require( '../../utils/options' );
const { ADMIN_STATE_PATH } = require( '../../playwright.config' );

/**
 * Set the email improvements feature flag.
 *
 * @param {string} baseURL The base URL.
 * @param {string} value   The value to set ('yes' or 'no').
 * @return {Promise<void>}
 */
const setFeatureFlag = async ( baseURL, value ) =>
	await setOption(
		request,
		baseURL,
		'woocommerce_feature_email_improvements_enabled',
		value
	);

/**
 * Set the email auto-sync feature flag.
 *
 * @param {string} baseURL The base URL.
 * @param {string} value   The value to set ('yes' or 'no').
 * @return {Promise<void>}
 */
const setAutoSyncFlag = async ( baseURL, value ) =>
	await setOption(
		request,
		baseURL,
		'woocommerce_email_auto_sync_with_theme',
		value
	);

test.describe( 'Email Style Sync', () => {
	test.use( { storageState: ADMIN_STATE_PATH } );

	test.beforeEach( async ( { baseURL } ) => {
		// Enable email improvements feature
		await setFeatureFlag( baseURL, 'yes' );
		// Ensure auto-sync is enabled by default
		await setAutoSyncFlag( baseURL, 'yes' );
		// Ensure color palette is not synced with theme
		await setOption(
			request,
			baseURL,
			'woocommerce_email_base_color',
			'#123456'
		);
	} );

	test.afterAll( async ( { baseURL } ) => {
		// Reset feature flags after tests
		await setFeatureFlag( baseURL, 'no' );
		await setAutoSyncFlag( baseURL, 'no' );
	} );

	test( 'Auto-sync toggle in email settings works correctly', async ( {
		page,
	} ) => {
		// Navigate to WooCommerce email settings
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=email' );

		const autoSyncToggle = page.locator(
			'.wc-settings-email-color-palette-auto-sync input[type="checkbox"]'
		);

		// Auto-sync is not available when theme is not in sync
		await expect( autoSyncToggle ).toBeHidden();

		// Sync color palette with theme
		await page.getByRole( 'button', { name: 'Sync with theme' } ).click();

		// Check initial state (should be enabled by default)
		await expect( autoSyncToggle ).toBeVisible();
		await expect( autoSyncToggle ).toBeChecked();

		// Save settings
		await page.locator( 'button.woocommerce-save-button' ).click();

		// Reload page and check if setting persisted
		await page.reload();
		await expect( autoSyncToggle ).toBeVisible();
		await expect( autoSyncToggle ).toBeChecked();

		// Toggle it off
		await autoSyncToggle.click();
		await expect( autoSyncToggle ).not.toBeChecked();

		// Change any color to check that auto-sync is hidden
		await page.locator( '#woocommerce_email_base_color' ).fill( '#123456' );
		await page.locator( '#woocommerce_email_base_color' ).blur();
		await expect( autoSyncToggle ).toBeHidden();

		// Save settings
		await page.locator( 'button.woocommerce-save-button' ).click();
	} );
} );
