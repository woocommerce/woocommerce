const { test, expect } = require( '@playwright/test' );
const { customer } = require( '../../test-data/data' );

const pages = [
	[ 'Orders', 'my-account/orders' ],
	[ 'Downloads', 'my-account/downloads' ],
	[ 'Addresses', 'my-account/edit-address' ],
	[ 'Account details', 'my-account/edit-account' ],
];

test.describe( 'My account page', () => {
	test.use( { storageState: process.env.CUSTOMERSTATE } );

	test( 'allows customer to login', async ( { page } ) => {
		await page.goto( 'my-account/' );

		await expect( page.locator( 'h1.entry-title' ) ).toContainText(
			'My account'
		);
		await expect(
			page.locator( 'div.woocommerce-MyAccount-content > p >> nth=0' )
		).toContainText( `${ customer.first_name } ${ customer.last_name }` );

		// assert that navigation is visible
		await expect(
			page.locator( '.woocommerce-MyAccount-navigation-link--dashboard' )
		).toContainText( 'Dashboard' );
		await expect(
			page.locator( '.woocommerce-MyAccount-navigation-link--orders' )
		).toContainText( 'Orders' );
		await expect(
			page.locator( '.woocommerce-MyAccount-navigation-link--downloads' )
		).toContainText( 'Downloads' );
		await expect(
			page.locator(
				'.woocommerce-MyAccount-navigation-link--edit-address'
			)
		).toContainText( 'Addresses' );
		await expect(
			page.locator(
				'.woocommerce-MyAccount-navigation-link--edit-account'
			)
		).toContainText( 'Account details' );
		await expect(
			page.locator(
				'.woocommerce-MyAccount-navigation-link--customer-logout'
			)
		).toContainText( 'Log out' );
	} );

	for ( let i = 0; i < pages.length; i++ ) {
		test( `allows customer to see ${ pages[ i ][ 0 ] } page`, async ( {
			page,
		} ) => {
			await page.goto( pages[ i ][ 1 ] );
			await expect( page.locator( 'h1.entry-title' ) ).toContainText(
				pages[ i ][ 0 ]
			);
		} );
	}
} );
