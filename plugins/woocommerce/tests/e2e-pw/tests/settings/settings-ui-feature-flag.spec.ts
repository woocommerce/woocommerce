/**
 * Internal dependencies
 */
import { expect, test, tags, request } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { setFeatureFlag, resetFeatureFlags } from '../../utils/features';
import { setOption } from '../../utils/options';

const getBaseURL = ( baseURL: string | undefined ): string => {
	if ( ! baseURL ) {
		throw new Error( 'Expected baseURL to be configured.' );
	}

	return baseURL;
};

test.describe( 'Settings UI feature flag', { tag: tags.NOT_E2E }, () => {
	test.use( { storageState: ADMIN_STATE_PATH } );

	test.beforeEach( async ( { baseURL } ) => {
		const url = getBaseURL( baseURL );

		await setFeatureFlag( request, url, 'settings-ui', false );
		await setOption( request, url, 'woocommerce_enable_reviews', 'yes' );
	} );

	test.afterAll( async ( { baseURL } ) => {
		const url = getBaseURL( baseURL );

		await resetFeatureFlags( request, url );
		await setOption( request, url, 'woocommerce_enable_reviews', 'yes' );
	} );

	test( 'does not mount the settings UI when the feature flag is disabled', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=products' );

		await expect(
			page.locator( '#woocommerce_enable_reviews' )
		).toBeVisible();
		await expect( page.locator( '[data-wc-settings-ui]' ) ).toHaveCount(
			0
		);
		await page.locator( '#woocommerce_enable_reviews' ).uncheck();
		await page.getByRole( 'button', { name: 'Save changes' } ).click();

		await expect( page.locator( 'div.updated.inline' ) ).toContainText(
			'Your settings have been saved.'
		);
		await expect(
			page.locator( '#woocommerce_enable_reviews' )
		).not.toBeChecked();
	} );
} );
