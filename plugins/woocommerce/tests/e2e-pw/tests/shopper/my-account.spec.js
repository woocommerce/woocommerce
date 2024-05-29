const { test, expect } = require( '@playwright/test' );
const { customer } = require( '../../test-data/data' );

const pages = [ 'Orders', 'Downloads', 'Addresses', 'Account details' ];

test.describe( 'My account page', () => {
	test.use( { storageState: process.env.CUSTOMERSTATE } );

	test( 'allows customer to login and navigate', async ( { page } ) => {
		await page.goto( 'my-account/' );

		await expect(
			page.getByRole( 'heading', { name: 'My account' } )
		).toBeVisible();
		await expect(
			page.getByText(
				`Hello ${ customer.first_name } ${ customer.last_name }`
			)
		).toBeVisible();

		for ( const accountPage of pages ) {
			await test.step( `customer can navigate to ${ accountPage } page`, async () => {
				await page
					.getByRole( 'link', { name: accountPage, exact: true } )
					.click();
				await expect(
					page.getByRole( 'heading', { name: accountPage } )
				).toBeVisible();
			} );
		}

		await expect(
			page.getByRole( 'link', { name: 'Log out', exact: true } )
		).toBeVisible();
	} );
} );
