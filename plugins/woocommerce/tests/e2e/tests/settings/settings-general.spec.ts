/**
 * External dependencies
 */
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { expect, test, tags } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

test.describe( 'WooCommerce General Settings', { tag: tags.SERVICES }, () => {
	test.use( { storageState: ADMIN_STATE_PATH } );
	const persistedSettingIds = [
		'woocommerce_allowed_countries',
		'woocommerce_specific_allowed_countries',
		'woocommerce_default_country',
	];
	const originalSettingValues = new Map< string, string | string[] >();

	test.beforeAll( async ( { restApi } ) => {
		for ( const settingId of persistedSettingIds ) {
			const response = await restApi.get(
				`${ WC_API_PATH }/settings/general/${ settingId }`
			);
			originalSettingValues.set( settingId, response.data.value );
		}
	} );

	test.afterAll( async ( { restApi } ) => {
		const update = Array.from(
			originalSettingValues,
			( [ id, value ] ) => ( { id, value } )
		);

		if ( update.length ) {
			await restApi.post( `${ WC_API_PATH }/settings/general/batch`, {
				update,
			} );
		}
	} );

	test(
		'Save Changes button is disabled by default and enabled only after changes.',
		{
			tag: [ tags.NON_CRITICAL, tags.NOT_E2E ],
		},
		async ( { page } ) => {
			await page.goto( 'wp-admin/admin.php?page=wc-settings' );

			// make sure the general tab is active
			await expect( page.locator( 'a.nav-tab-active' ) ).toContainText(
				'General'
			);

			// See the Save changes button is disabled.
			await expect(
				page.getByRole( 'button', { name: 'Save changes' } )
			).toBeDisabled();

			const allExceptCountriesRow = page.locator( 'tr' ).filter( {
				has: page.locator( '#woocommerce_all_except_countries' ),
			} );
			const specificCountriesRow = page.locator( 'tr' ).filter( {
				has: page.locator( '#woocommerce_specific_allowed_countries' ),
			} );

			// Changing the selling location marks the form as dirty and exposes
			// only the conditional country control for the selected mode.
			await page
				.locator( '#woocommerce_allowed_countries' )
				.selectOption( 'all_except' );
			await expect( allExceptCountriesRow ).toBeVisible();
			await expect( specificCountriesRow ).toBeHidden();

			// See the Save changes button is now enabled.
			await expect( page.locator( 'text=Save changes' ) ).toBeEnabled();

			await page
				.locator( '#woocommerce_allowed_countries' )
				.selectOption( 'all' );
			await expect( allExceptCountriesRow ).toBeHidden();
			await expect( specificCountriesRow ).toBeHidden();

			await page
				.locator( '#woocommerce_allowed_countries' )
				.selectOption( 'specific' );
			await expect( allExceptCountriesRow ).toBeHidden();
			await expect( specificCountriesRow ).toBeVisible();
			await page
				.locator( '#woocommerce_specific_allowed_countries' )
				.selectOption( 'US' );

			// Change the base location and persist the assembled form.
			await page
				.locator( 'select[name="woocommerce_default_country"]' )
				.selectOption( 'US:NY' );
			await page.getByRole( 'button', { name: 'Save changes' } ).click();
			await expect( page.locator( 'div.updated.inline' ) ).toContainText(
				'Your settings have been saved.'
			);

			await page.reload();
			await expect(
				page.locator( '#woocommerce_allowed_countries' )
			).toHaveValue( 'specific' );
			await expect(
				page.locator( '#woocommerce_specific_allowed_countries' )
			).toHaveValues( [ 'US' ] );
			await expect(
				page.locator( 'select[name="woocommerce_default_country"]' )
			).toHaveValue( 'US:NY' );
			await expect(
				page.getByRole( 'button', { name: 'Save changes' } )
			).toBeDisabled();
		}
	);
} );
