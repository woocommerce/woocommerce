/**
 * Internal dependencies
 */
import { expect, test } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { wpCLI } from '../../utils/cli';

/**
 * Wrap a value as a single shell argument.
 *
 * `wpCLI()` builds one command string and runs it through `exec()`, which hands it to a shell, so
 * an interpolated value has to survive shell parsing. A permalink base can legitimately contain a
 * single quote — `wc_sanitize_permalink()` leaves them intact, so a custom base of `shop's` is
 * stored verbatim — and an unquoted one would break the command. `'\''` ends the quoted run,
 * emits a literal quote, and opens the next one.
 *
 * @param value Value to pass as a single argument.
 * @return The value quoted for the shell.
 */
const asShellArgument = ( value: string ) =>
	`'${ value.replaceAll( "'", `'\\''` ) }'`;

test.describe( 'Product permalink settings', () => {
	test.use( { storageState: ADMIN_STATE_PATH } );

	test( 'saved product permalink structures stay selected after a reload', async ( {
		page,
	} ) => {
		// Every WP-CLI call in this spec is a plain database operation, so plugins and themes are
		// skipped: earlier specs in the serial suite can leave heavyweight extensions installed
		// (the onboarding wizard installs the default set), and booting them under WP-CLI can
		// exhaust the CLI container's memory limit before the command runs.
		const optionCliFlags = '--skip-plugins --skip-themes';
		// Snapshot the whole option rather than the visible form state: the saved product base is
		// normalized on the way in, so replaying the form values would not always restore the
		// bytes the option started with.
		const readPermalinks = async () =>
			(
				await wpCLI(
					`wp option get woocommerce_permalinks --format=json ${ optionCliFlags }`
				)
			).stdout.trim();

		// The snapshot only has to precede the first save, and spawning the WP-CLI container costs
		// seconds, so overlap it with the page load rather than queueing behind it.
		const [ , originalPermalinks ] = await Promise.all( [
			page.goto( 'wp-admin/options-permalink.php' ),
			readPermalinks(),
		] );

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

		try {
			const defaultRadio = productPermalinkRadios.nth( 0 );
			const shopBaseRadio = productPermalinkRadios.nth( 1 );
			const shopCategoryRadio = productPermalinkRadios.nth( 2 );
			const defaultRow = page
				.getByRole( 'row' )
				.filter( { has: defaultRadio } );
			// Derive the base from the rendered preview rather than hardcoding a translated
			// product slug. Matching the segment before `sample-product` off the end of the URL
			// keeps this independent of the install layout, so a subdirectory install needs no
			// separate site-path arithmetic.
			const defaultPreview =
				(
					await defaultRow
						.locator( 'code.non-default-example' )
						.textContent()
				)?.trim() ?? '';
			const previewBase = defaultPreview.match(
				/\/([^/]+)\/sample-product\/$/
			);
			expect(
				previewBase,
				`Unexpected Default preview: ${ defaultPreview }`
			).not.toBeNull();
			const expectedBareSlug = previewBase?.[ 1 ] ?? '';
			const expectedDefaultBase = `/${ expectedBareSlug }/`;

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

			// A single Tab from the checked radio lands on the Custom base field, because the
			// radios share one tab stop. Focus alone must leave the checked radio where it is:
			// flipping it there would move a keyboard user off the structure the store uses,
			// undoing what this screen was fixed to report.
			const customSelection = page.locator(
				'#woocommerce_custom_selection'
			);

			await customBase.focus();

			await expect( defaultRadio ).toBeChecked();
			await expect( customSelection ).not.toBeChecked();
			await expect( customBase ).toHaveValue( expectedDefaultBase );

			// A real click does select Custom base, and leaves the prefilled Default structure in
			// the field. Saving from there posts that structure through the custom branch, which
			// the save path normalizes back to Default's bare slug: the slash-prefixed form it
			// would otherwise store builds broken rewrite rules under index.php (PATHINFO)
			// permalinks.
			await customBase.click();

			await expect( customSelection ).toBeChecked();
			await expect( customBase ).toHaveValue( expectedDefaultBase );

			await saveAndReload();

			await expect( defaultRadio ).toBeChecked();
			await expect( customBase ).toHaveValue( expectedDefaultBase );

			const storedPermalinks = JSON.parse( await readPermalinks() );
			expect( storedPermalinks.product_base ).toBe( expectedBareSlug );
		} finally {
			await wpCLI(
				`wp option update woocommerce_permalinks ${ asShellArgument(
					originalPermalinks
				) } --format=json ${ optionCliFlags }`
			);
			// The option alone does not rebuild the persisted rewrite rules the front end matches
			// against. Emptying them makes WordPress regenerate on the next request with every
			// plugin loaded — `wp rewrite flush` under --skip-plugins would persist a rule set
			// missing all plugin rewrites.
			await wpCLI(
				`wp option update rewrite_rules '' ${ optionCliFlags }`
			);
		}
	} );
} );
