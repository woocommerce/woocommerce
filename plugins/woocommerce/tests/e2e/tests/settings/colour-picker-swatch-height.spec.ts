/**
 * Internal dependencies
 */
import { test, expect } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

test.describe( 'Colour picker swatch height on Email settings', () => {
	test.use( { storageState: ADMIN_STATE_PATH } );

	test( 'colour swatch is correctly sized with WP 7.0 body class', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=email' );

		await expect( page.locator( 'body' ) ).toHaveClass(
			/wc-wp-version-gte-53/
		);

		// The swatch below is the one the settings screen renders, not a mounted
		// fixture, so the assertions also prove the classic colour fields are still
		// on this screen.
		const swatch = page.locator( '.colorpickpreview' ).first();
		await expect( swatch ).toBeVisible();

		// Measure the painted box rather than the declared CSS. The swatch carries a
		// 1px border, so the two differ wherever the rule sets no box-sizing, and
		// making them agree at 40px is the whole point of the WP 7.0 rule.
		await page.evaluate( () => {
			document.body.classList.remove( 'wc-wp-version-gte-70' );
		} );

		// Pre-WP 7.0: 30px declared, content-box, so 32px painted. This figure is for
		// the wide layout; the same rule has a max-width: 782px branch that declares
		// 40px, which would paint 42px.
		expect( await swatch.boundingBox() ).toMatchObject( {
			height: 32,
			width: 32,
		} );

		await page.evaluate( () => {
			document.body.classList.add( 'wc-wp-version-gte-70' );
		} );

		// WP 7.0+: 40px declared with box-sizing: border-box, so 40px painted, which
		// is what matches the taller WP 7.0 inputs.
		expect( await swatch.boundingBox() ).toMatchObject( {
			height: 40,
			width: 40,
		} );
	} );
} );
