/**
 * Internal dependencies
 */
import { tags, test, expect } from '../../fixtures/fixtures.js';
import { ADMIN_STATE_PATH } from '../../playwright.config.js';
import { WC_API_PATH } from '../../utils/api-client';

test.describe(
	'WooCommerce woo.com Settings',
	{
		tag: [ tags.SERVICES, tags.SKIP_ON_WPCOM ],
	},
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		test.beforeAll( async ( { restApi } ) => {
			await restApi.put(
				`${ WC_API_PATH }/settings/advanced/woocommerce_allow_tracking`,
				{
					value: 'no',
				}
			);
			await restApi.put(
				`${ WC_API_PATH }/settings/advanced/woocommerce_show_marketplace_suggestions`,
				{
					value: 'no',
				}
			);
		} );

		test( 'can enable analytics tracking', async ( { page } ) => {
			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=advanced&section=woocommerce_com'
			);

			// enable analytics tracking
			await page
				.getByLabel( 'Allow usage of WooCommerce to be tracked' )
				.check();
			await page.getByRole( 'button', { name: 'Save changes' } ).click();

			// confirm setting saved
			await expect( page.locator( 'div.updated.inline' ) ).toContainText(
				'Your settings have been saved.'
			);
			await expect(
				page.getByLabel( 'Allow usage of WooCommerce to be tracked' )
			).toBeChecked();
		} );

		test( 'can enable marketplace suggestions', async ( { page } ) => {
			await page.goto(
				'wp-admin/admin.php?page=wc-settings&tab=advanced&section=woocommerce_com'
			);

			// enable marketplace suggestions
			await page
				.getByLabel( 'Display suggestions within WooCommerce' )
				.check();
			await page.getByRole( 'button', { name: 'Save changes' } ).click();

			// confirm setting saved
			await expect( page.locator( 'div.updated.inline' ) ).toContainText(
				'Your settings have been saved.'
			);
			await expect(
				page.getByLabel( 'Display suggestions within WooCommerce' )
			).toBeChecked();
		} );
	}
);
