/**
 * External dependencies
 */
import { test, expect } from '../../fixtures/fixtures';

/**
 * Internal dependencies
 */
import { ADMIN_STATE_PATH } from '../../playwright.config';

test.describe( 'Colour picker swatch height on Email settings', () => {
	test.use( { storageState: ADMIN_STATE_PATH } );

	test( 'colour swatch is correctly sized with WP 7.0 body class', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=email' );

		// Add the WP 7.0+ body class to simulate WP 7.0 environment.
		await page.evaluate( () => {
			document.body.classList.add( 'wc-wp-version-gte-70' );
		} );

		const swatch = page.locator( '.colorpickpreview' ).first();
		await expect( swatch ).toBeVisible();

		const swatchBox = await swatch.boundingBox();
		if ( ! swatchBox ) {
			throw new Error( 'Could not get bounding box for swatch' );
		}

		// With the gte-70 class, the swatch should be 40px to match WP 7.0 input height.
		expect( swatchBox.height ).toBe( 40 );
		expect( swatchBox.width ).toBe( 40 );
	} );
} );
