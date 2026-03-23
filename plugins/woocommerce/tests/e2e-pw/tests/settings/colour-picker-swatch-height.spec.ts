/**
 * External dependencies
 */
import { test, expect } from '@playwright/test';

/**
 * Internal dependencies
 */
import { ADMIN_STATE_PATH } from '../../playwright.config';

test.describe( 'Colour picker swatch height on Email settings', () => {
	test.use( { storageState: ADMIN_STATE_PATH } );

	test( 'colour swatch height matches the adjacent input height', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=email' );

		// Add the WP 7.0+ body class to simulate WP 7.0 environment.
		await page.evaluate( () => {
			document.body.classList.add( 'wc-wp-version-gte-70' );
		} );

		const swatch = page.locator( '.colorpickpreview' ).first();
		await expect( swatch ).toBeVisible();

		const input = page.locator( 'input.colorpick' ).first();
		await expect( input ).toBeVisible();

		const swatchBox = await swatch.boundingBox();
		const inputBox = await input.boundingBox();

		expect( swatchBox ).not.toBeNull();
		expect( inputBox ).not.toBeNull();

		// With the gte-70 class, both swatch and input should be 40px tall.
		expect( swatchBox.height ).toBe( 40 );

		// Swatch height should match the input height (within 2px tolerance for borders).
		expect( Math.abs( swatchBox.height - inputBox.height ) ).toBeLessThanOrEqual( 2 );
	} );
} );
