/**
 * Internal dependencies
 */
import { tags, test, expect } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

/**
 * Canary for the WooCommerce.com settings screen.
 *
 * The two persistence tests that used to live here moved to
 * WC_Settings_Advanced_Test::test_save_persists_woocommerce_com_checkbox_options, which asserts
 * what the server does with the posted values. Nothing else in the suite opens this section.
 *
 * The label strings themselves are not unowned: tests/e2e/tests/api-tests/settings/settings-crud.test.ts
 * asserts both as the REST description field, from the same `desc` in class-wc-settings-advanced.php,
 * so a rename would fail there too. What no other test covers is that this section renders at all,
 * and that each label is associated with its checkbox — which is what getByRole resolves by
 * accessible name, and what makes a privacy control reachable by assistive technology. The API test
 * reads a JSON field and renders nothing.
 *
 * This asserts only that. It deliberately does not save anything: persistence is the PHPUnit
 * test's job.
 */
test.describe(
	'WooCommerce.com Settings screen',
	{
		tag: [ tags.SERVICES, tags.SKIP_ON_WPCOM ],
	},
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		test( 'renders both opt-in controls with their accessible labels', async ( {
			page,
		} ) => {
			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=advanced&section=woocommerce_com'
			);

			await expect(
				page.getByRole( 'checkbox', {
					name: 'Allow usage of WooCommerce to be tracked',
				} )
			).toBeVisible();
			await expect(
				page.getByRole( 'checkbox', {
					name: 'Display suggestions within WooCommerce',
				} )
			).toBeVisible();
		} );
	}
);
