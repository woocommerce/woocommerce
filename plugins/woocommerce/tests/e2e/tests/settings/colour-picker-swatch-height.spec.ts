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

		// Email Improvements no longer renders the classic color fields. Mount the
		// exact structural classes emitted by WC_Admin_Settings so this test stays
		// focused on the real Woo admin stylesheet loaded by the settings page.
		await page.evaluate( () => {
			document.body.classList.remove( 'wc-wp-version-gte-70' );

			const fixture = document.createElement( 'div' );
			fixture.className = 'woocommerce';
			fixture.innerHTML = `
				<table class="form-table">
					<tbody>
						<tr>
							<td class="forminp-color">
								<span class="colorpickpreview" data-testid="colour-picker-swatch">&nbsp;</span>
							</td>
						</tr>
					</tbody>
				</table>
			`;
			document.body.appendChild( fixture );
		} );

		const swatch = page.getByTestId( 'colour-picker-swatch' );
		await expect( swatch ).toBeVisible();

		await expect( swatch ).toHaveCSS( 'height', '30px' );
		await expect( swatch ).toHaveCSS( 'width', '30px' );

		// Add the WP 7.0+ body class to activate the compatibility rule.
		await page.evaluate( () => {
			document.body.classList.add( 'wc-wp-version-gte-70' );
		} );

		await expect( swatch ).toHaveCSS( 'height', '40px' );
		await expect( swatch ).toHaveCSS( 'width', '40px' );
	} );
} );
