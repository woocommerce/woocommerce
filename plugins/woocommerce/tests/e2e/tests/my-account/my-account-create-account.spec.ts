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
const registrationSettingIds = [
	'woocommerce_enable_myaccount_registration',
	'woocommerce_registration_generate_password',
];

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
		await restApi.put(
			`${ accountSettingsPath }/woocommerce_registration_generate_password`,
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

	test( 'can create a new account via my account', async ( { page } ) => {
		await page.goto( 'my-account/' );

		await expect(
			page.locator( '.woocommerce-form-register' )
		).toBeVisible();

		await page.locator( 'input#reg_email' ).fill( customerEmailAddress );
		await page.locator( 'button[name="register"]' ).click();

		await expect(
			page.getByRole( 'heading', { name: 'My account' } )
		).toBeVisible();
		await expect( page.locator( 'text=Log out' ).first() ).toBeVisible();

		await page.goto( 'my-account/edit-account/' );
		await expect( page.locator( '#account_email' ) ).toHaveValue(
			customerEmailAddress
		);
	} );

	test( 'shows the temporary password reminder on My Account after registration', async ( {
		page,
		restApi,
	} ) => {
		const email = `temporary-password-${ Date.now() }@example.com`;

		try {
			await page.goto( 'my-account/' );
			await expect(
				page.locator( '.woocommerce-form-register' )
			).toBeVisible();
			await expect( page.locator( '#reg_password' ) ).toBeHidden();
			await page.locator( '#reg_email' ).fill( email );
			await page.locator( 'button[name="register"]' ).click();

			await expect( page ).toHaveURL( /\/my-account\/?$/ );
			await expect(
				page.getByRole( 'status' ).filter( {
					has: page.getByRole( 'link', { name: 'Resend' } ),
				} )
			).toContainText(
				'Your account is using a temporary password. We emailed you a link to change your password.'
			);
		} finally {
			const response = await restApi.get(
				`${ WC_API_PATH }/customers?email=${ encodeURIComponent( email ) }`
			);
			for ( const customer of response.data ) {
				await restApi.delete(
					`${ WC_API_PATH }/customers/${ customer.id }`,
					{ force: true }
				);
			}
		}
	} );
} );
