/**
 * External dependencies
 */
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { expect, test } from '../../fixtures/fixtures';

const customerEmailAddress = `john.doe.${ Date.now() }@example.com`;
const originalSettingValues = new Map< string, string >();
const accountSettingsPath = `${ WC_API_PATH }/settings/account`;
const registrationSettingIds = [ 'woocommerce_enable_myaccount_registration' ];

test.describe( 'Shopper My Account Create Account', () => {
	test.beforeAll( async ( { restApi } ) => {
		for ( const settingId of registrationSettingIds ) {
			const response = await restApi.get(
				`${ accountSettingsPath }/${ settingId }`
			);
			originalSettingValues.set( settingId, response.data.value );
		}

		await restApi.put(
			`${ accountSettingsPath }/woocommerce_enable_myaccount_registration`,
			{
				value: 'yes',
			}
		);
	} );

	test.afterAll( async ( { restApi } ) => {
		const cleanupErrors: unknown[] = [];
		try {
			const response = await restApi.get(
				`${ WC_API_PATH }/customers?email=${ encodeURIComponent(
					customerEmailAddress
				) }`
			);
			for ( const customer of response.data ) {
				await restApi.delete(
					`${ WC_API_PATH }/customers/${ customer.id }`,
					{ force: true }
				);
			}
		} catch ( error ) {
			cleanupErrors.push( error );
		} finally {
			for ( const [ settingId, value ] of originalSettingValues ) {
				try {
					await restApi.put(
						`${ accountSettingsPath }/${ settingId }`,
						{ value }
					);
				} catch ( error ) {
					cleanupErrors.push(
						new Error(
							`Failed to restore ${ settingId }: ${ String( error ) }`
						)
					);
				}
			}
		}
		if ( cleanupErrors.length ) {
			throw new AggregateError(
				cleanupErrors,
				'Failed to clean up My Account registration test state.'
			);
		}
	} );

	test( 'can create a new account via my account', async ( {
		page,
		restApi,
	} ) => {
		await page.goto( 'my-account/' );

		await expect(
			page.locator( '.woocommerce-form-register' )
		).toBeVisible();
		// New accounts get a link by email to set their password, so registration does not ask for one.
		await expect( page.locator( '#reg_password' ) ).toHaveCount( 0 );

		await page.locator( 'input#reg_email' ).fill( customerEmailAddress );
		await page.locator( 'button[name="register"]' ).click();

		await expect(
			page.getByText( 'Your account has been created.' )
		).toBeVisible();
		await expect(
			page.getByText(
				`Check ${ customerEmailAddress } for a link to set your password.`
			)
		).toBeVisible();
		await expect(
			page.getByRole( 'link', { name: 'Get a new link' } )
		).toBeVisible();

		// The customer is not logged in until they set a password.
		await expect( page.locator( 'text=Log out' ) ).toHaveCount( 0 );
		await expect( page.locator( '#username' ) ).toBeVisible();

		const response = await restApi.get(
			`${ WC_API_PATH }/customers?email=${ encodeURIComponent(
				customerEmailAddress
			) }`
		);
		expect( response.data ).toHaveLength( 1 );
	} );
} );
