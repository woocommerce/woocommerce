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
		// Start listening for console errors
		page.on( 'console', ( msg ) => {
			if ( msg.type() === 'error' ) {
				console.log( `Browser console error: ${ msg.text() }` );
			}
		} );

		// Navigate to WooCommerce email settings
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=email' );

		// Take a screenshot after page load to verify the page loaded correctly
		await page.screenshot( { path: 'email-settings-page-load.png' } );

		const autoSyncToggle = page.locator(
			'.wc-settings-email-color-palette-auto-sync input[type="checkbox"]'
		);

		// Auto-sync is not available when theme is not in sync
		await expect( autoSyncToggle ).toBeHidden();

		// Check if the sync button exists in the DOM (even if not visible)
		const syncButtonCount = await page
			.getByRole( 'button', { name: 'Sync with theme' } )
			.count();
		console.log( 'Sync button count in DOM:', syncButtonCount );

		// Take a screenshot to see the state of the page before clicking
		await page.screenshot( { path: 'before-sync-button-click.png' } );

		// Get the HTML content of the color palette section to see what's being rendered
		const colorPaletteHTML = await page.evaluate( () => {
			const element = document.querySelector(
				'.wc-settings-email-color-palette-buttons'
			);
			return element ? element.outerHTML : 'Element not found';
		} );
		console.log( 'Color palette HTML:', colorPaletteHTML );

		// Try to find the button using different selectors
		console.log( 'Trying alternative selectors for the Sync button' );

		// Try using CSS selector
		const syncButtonCSS = page.locator(
			'button:has-text("Sync with theme")'
		);
		const syncButtonCSSCount = await syncButtonCSS.count();
		console.log( 'Sync button CSS selector count:', syncButtonCSSCount );

		// Wait for the button to be visible with a longer timeout
		try {
			await page
				.getByRole( 'button', { name: 'Sync with theme' } )
				.waitFor( { state: 'visible', timeout: 30000 } );

			// Sync color palette with theme using role selector
			await page
				.getByRole( 'button', { name: 'Sync with theme' } )
				.click( { timeout: 30000 } );
		} catch ( error ) {
			console.log(
				'Error waiting for or clicking button by role:',
				error.message
			);

			// Try clicking using CSS selector as fallback
			if ( syncButtonCSSCount > 0 ) {
				console.log( 'Trying to click using CSS selector' );
				await syncButtonCSS.first().click( { timeout: 30000 } );
			} else {
				console.log(
					'Button not found with any selector, test will fail'
				);
				// Take a final screenshot to see the state of the page
				await page.screenshot( { path: 'button-not-found.png' } );
				throw error;
			}
		}

		// Check initial state (should be enabled by default)
		await expect( autoSyncToggle ).toBeVisible();
		await expect( autoSyncToggle ).toBeChecked();

		// Save settings
		await page.locator( 'button.woocommerce-save-button' ).click();

		await expect(
			page
				.locator( '#message' )
				.filter( { hasText: 'Your settings have been saved' } )
		).toBeVisible();

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
