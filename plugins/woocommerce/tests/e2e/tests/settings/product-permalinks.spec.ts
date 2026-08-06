/**
 * Internal dependencies
 */
import { expect, test } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

test.describe( 'Product permalink settings', () => {
	test.use( { storageState: ADMIN_STATE_PATH } );

	test( 'Default exposes its effective base as the canonical Custom value', async ( {
		page,
		baseURL,
	} ) => {
		if ( ! baseURL ) {
			throw new Error( 'Playwright baseURL is required.' );
		}

		await page.goto( 'wp-admin/options-permalink.php' );

		const productPermalinkRadios = page.locator(
			'input[name="product_permalink"]'
		);
		const customBase = page.locator( '#woocommerce_permalink_structure' );
		const saveChanges = page.locator( '#submit' );

		await expect( productPermalinkRadios ).toHaveCount( 4 );

		const originalCheckedIndex = await productPermalinkRadios.evaluateAll(
			( radios ) =>
				radios.findIndex(
					( radio ) => ( radio as HTMLInputElement ).checked
				)
		);
		const originalCustomBase = await customBase.inputValue();
		expect( originalCheckedIndex ).toBeGreaterThanOrEqual( 0 );

		try {
			const defaultRadio = productPermalinkRadios.nth( 0 );
			const shopBaseRadio = productPermalinkRadios.nth( 1 );
			const defaultRow = page
				.getByRole( 'row' )
				.filter( { has: defaultRadio } );
			const defaultPreview = new URL(
				(
					await defaultRow
						.locator( 'code.non-default-example' )
						.textContent()
				)?.trim() ?? ''
			);
			// Strip the site and sample-product paths from the preview so the test derives
			// the canonical `/base/` without hardcoding a translated product slug.
			const sitePath = new URL( baseURL ).pathname.replace( /\/$/, '' );
			const expectedDefaultBase = defaultPreview.pathname
				.slice( sitePath.length )
				.replace( /sample-product\/$/, '' );

			expect( expectedDefaultBase ).toMatch( /^\/.+\/$/ );
			await expect( customBase ).toHaveValue( expectedDefaultBase );

			await shopBaseRadio.check();
			await expect( customBase ).toHaveValue(
				await shopBaseRadio.inputValue()
			);

			await defaultRadio.check();
			await expect( defaultRadio ).toHaveValue( '' );
			await expect( customBase ).toHaveValue( expectedDefaultBase );

			await Promise.all( [
				page.waitForResponse(
					( response ) =>
						response.request().method() === 'POST' &&
						response.url().includes( 'options-permalink.php' )
				),
				saveChanges.click(),
			] );

			await expect( defaultRadio ).toBeChecked();
			await expect( defaultRadio ).toHaveValue( '' );
			await expect( customBase ).toHaveValue( expectedDefaultBase );
		} finally {
			await page.goto( 'wp-admin/options-permalink.php' );

			const originalRadio = page
				.locator( 'input[name="product_permalink"]' )
				.nth( originalCheckedIndex );

			await originalRadio.check();
			if ( originalCheckedIndex === 3 ) {
				await page
					.locator( '#woocommerce_permalink_structure' )
					.fill( originalCustomBase );
			}

			await Promise.all( [
				page.waitForResponse(
					( response ) =>
						response.request().method() === 'POST' &&
						response.url().includes( 'options-permalink.php' )
				),
				page.locator( '#submit' ).click(),
			] );
		}
	} );
} );
