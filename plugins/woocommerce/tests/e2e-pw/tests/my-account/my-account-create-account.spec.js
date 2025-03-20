/**
 * Internal dependencies
 */
import { expect, test } from '../../fixtures/fixtures';
import { WC_API_PATH } from '../../utils/api-client';

const customerEmailAddress = `john.doe.${ Date.now() }@example.com`;

test.describe( 'Shopper My Account Create Account', () => {
	test.beforeAll( async ( { restApi } ) => {
		await restApi.put(
			`${ WC_API_PATH }/settings/account/woocommerce_enable_myaccount_registration`,
			{
				value: 'yes',
			}
		);
	} );

	test.afterAll( async ( { restApi } ) => {
		// get a list of all customers and delete the one we created
		await restApi
			.get( `${ WC_API_PATH }/customers` )
			.then( ( response ) => {
				for ( let i = 0; i < response.data.length; i++ ) {
					if ( response.data[ i ].email === customerEmailAddress ) {
						restApi.delete(
							`${ WC_API_PATH }/customers/${ response.data[ i ].id }`,
							{
								force: true,
							}
						);
					}
				}
			} );

		await restApi.put(
			`${ WC_API_PATH }/settings/account/woocommerce_enable_myaccount_registration`,
			{
				value: 'no',
			}
		);
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
} );
