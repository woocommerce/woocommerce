/**
 * Internal dependencies
 */
import { expect, test } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

test.describe( 'Product permalink settings', () => {
	test.use( { storageState: ADMIN_STATE_PATH } );

	test( 'saved product permalink structures stay selected after a reload', async ( {
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
		const saveChanges = page.getByRole( 'button', {
			name: 'Save Changes',
		} );
		// WordPress processes the POST and redirects back, so the assertions after a save have to
		// run against the reloaded document. Waiting on the POST response alone would let them
		// resolve against the pre-submit DOM, which still shows whatever was just checked — the
		// reload this test exists to verify would never be observed.
		const saveAndReload = async () => {
			const reloaded = page.waitForEvent( 'load' );
			await saveChanges.click();
			await reloaded;
		};

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
			const shopCategoryRadio = productPermalinkRadios.nth( 2 );
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
			// Derive the canonical `/base/` from the preview rather than hardcoding a translated
			// product slug. `/\/$/` drops the trailing slash so a root install contributes an
			// empty prefix and a subdirectory install (`/wp/`) contributes `/wp`.
			const sitePath = new URL( baseURL ).pathname.replace( /\/$/, '' );
			// Assert the example segment first, so a changed preview fails here instead of
			// silently yielding a wrong base.
			expect( defaultPreview.pathname ).toMatch( /\/sample-product\/$/ );
			const expectedDefaultBase = defaultPreview.pathname
				.slice( sitePath.length )
				.replace( /sample-product\/$/, '' );

			// A non-empty, slash-delimited path such as `/product/`.
			expect( expectedDefaultBase ).toMatch( /^\/.+\/$/ );

			// Establish Default as the starting point rather than assuming the store already uses
			// it — the preceding assertion about the Custom field only holds from a known state.
			await defaultRadio.check();
			await saveAndReload();
			await expect( defaultRadio ).toBeChecked();
			await expect( customBase ).toHaveValue( expectedDefaultBase );

			// Shop base and Shop base with category post their structure verbatim; Default posts an
			// empty value and relies on the data attribute. Issue #29050 reported all three
			// reverting to Custom base, so each one round-trips through a real save here.
			for ( const radio of [ shopBaseRadio, shopCategoryRadio ] ) {
				const structure = await radio.inputValue();

				await radio.check();
				await expect( customBase ).toHaveValue( structure );

				await saveAndReload();

				await expect( radio ).toBeChecked();
				await expect( customBase ).toHaveValue( structure );
			}

			await defaultRadio.check();
			await expect( defaultRadio ).toHaveValue( '' );
			await expect( customBase ).toHaveValue( expectedDefaultBase );

			await saveAndReload();

			await expect( defaultRadio ).toBeChecked();
			await expect( defaultRadio ).toHaveValue( '' );
			await expect( customBase ).toHaveValue( expectedDefaultBase );
		} finally {
			await page.goto( 'wp-admin/options-permalink.php' );

			await productPermalinkRadios.nth( originalCheckedIndex ).check();
			if ( originalCheckedIndex === 3 ) {
				await customBase.fill( originalCustomBase );
			}

			await saveAndReload();
		}
	} );
} );
