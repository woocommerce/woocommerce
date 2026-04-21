/**
 * External dependencies
 */
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { expect, test, tags } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

/**
 * Locks the "modern-settings flag off = zero change" guarantee for the modernised
 * settings SDK.
 *
 * The `modern-settings` feature flag is disabled by default in the e2e
 * environment (see plugins/woocommerce/includes/react-admin/feature-config.php),
 * so no extra setup is needed for the flag-off state. These tests fail loudly
 * if any code path mounts the React renderer un-gated from the flag, or breaks
 * the legacy save form.
 */
test.describe(
	'Modernised Settings SDK > flag off',
	{ tag: tags.SERVICES },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		const ORIGINAL_POSTCODE = '94107';
		const NEW_POSTCODE = '94016';

		test.afterAll( async ( { restApi } ) => {
			await restApi.put(
				`${ WC_API_PATH }/settings/general/woocommerce_store_postcode`,
				{
					value: ORIGINAL_POSTCODE,
				}
			);
		} );

		test( 'WooCommerce General settings does not render the modern React mount div when modern-settings flag is off', async ( {
			page,
		} ) => {
			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=general'
			);

			// The General tab should be active and rendering the legacy form.
			await expect( page.locator( 'a.nav-tab-active' ) ).toContainText(
				'General'
			);

			// Zero React mount nodes should be present anywhere in the page.
			await expect(
				page.locator( '[data-wc-modern-settings]' )
			).toHaveCount( 0 );

			// The legacy form should still be rendered (sanity: at least one
			// known general-settings input must be present).
			await expect(
				page.locator( '#woocommerce_store_postcode' )
			).toBeVisible();
		} );

		test( 'WooCommerce General settings still saves changes via the legacy form when flag is off', async ( {
			page,
			restApi,
		} ) => {
			// Reset to the known baseline before mutating, so the test is
			// independent of state left by earlier runs.
			await restApi.put(
				`${ WC_API_PATH }/settings/general/woocommerce_store_postcode`,
				{
					value: ORIGINAL_POSTCODE,
				}
			);

			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=general'
			);

			await page
				.locator( '#woocommerce_store_postcode' )
				.fill( NEW_POSTCODE );

			await page.getByRole( 'button', { name: 'Save changes' } ).click();

			await expect( page.locator( 'div.updated.inline' ) ).toContainText(
				'Your settings have been saved.'
			);

			// The new value should be persisted across a reload.
			await page.reload();
			await expect(
				page.locator( '#woocommerce_store_postcode' )
			).toHaveValue( NEW_POSTCODE );
		} );
	}
);
