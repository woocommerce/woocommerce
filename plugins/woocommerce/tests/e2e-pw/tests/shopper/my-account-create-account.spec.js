const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const customerEmailAddress = 'john.doe.test@example.com';

test.describe( 'Shopper My Account Create Account', () => {
	test.beforeAll( async ( { baseURL } ) => {
		let customerId;

		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.put(
			'settings/account/woocommerce_enable_myaccount_registration',
			{
				value: 'yes',
			}
		);

		// make sure the test customer does not exist
		await api
			.get( `customers?email=${ customerEmailAddress }` )
			.then( ( response ) => {
				const testCustomer = response.data.find(
					( customer ) => customer.email === customerEmailAddress
				);

				if ( testCustomer ) {
					customerId = testCustomer.id;
				}
			} );
		if ( customerId ) {
			console.log(
				`Customer with email ${ customerEmailAddress } already exists. Deleting it before continuing with the test.`
			);
			await api.delete( `customers/${ customerId }`, { force: true } );
		}
	} );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// get a list of all customers
		await api.get( 'customers' ).then( ( response ) => {
			for ( let i = 0; i < response.data.length; i++ ) {
				if ( response.data[ i ].email === customerEmailAddress ) {
					api.delete( `customers/${ response.data[ i ].id }`, {
						force: true,
					} );
				}
			}
		} );
		await api.put(
			'settings/account/woocommerce_enable_myaccount_registration',
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

		await expect( page.locator( 'h1.entry-title' ) ).toContainText(
			'My account'
		);
		await expect( page.locator( 'text=Log out' ).first() ).toBeVisible();

		await page.goto( 'my-account/edit-account/' );
		await expect( page.locator( '#account_email' ) ).toHaveValue(
			customerEmailAddress
		);
	} );
} );
