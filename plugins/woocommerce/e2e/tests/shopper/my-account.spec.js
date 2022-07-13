const { test, expect } = require( '@playwright/test' );

const pages = [
	[ 'Orders', 'my-account/orders' ],
	[ 'Downloads', 'my-account/downloads' ],
	[ 'Addresses', 'my-account/edit-address' ],
	[ 'Account details', 'my-account/edit-account' ],
];

test.describe( 'My account page', () => {
	test.use( { storageState: 'e2e/storage/customerState.json' } );

	test( 'allows customer to login', async ( { page } ) => {
		await page.goto( 'my-account/' );

		await expect( page.locator( 'h1.entry-title' ) ).toContainText(
			'My account'
		);
		await expect(
			page.locator( 'div.woocommerce-MyAccount-content > p >> nth=0' )
		).toContainText( 'Jane Smith' );

		// assert that navigation is visible
		await expect(
			page.locator( '.woocommerce-MyAccount-navigation-link >> nth=0' )
		).toContainText( 'Dashboard' );
		await expect(
			page.locator( '.woocommerce-MyAccount-navigation-link >> nth=1' )
		).toContainText( 'Orders' );
		await expect(
			page.locator( '.woocommerce-MyAccount-navigation-link >> nth=2' )
		).toContainText( 'Downloads' );
		await expect(
			page.locator( '.woocommerce-MyAccount-navigation-link >> nth=3' )
		).toContainText( 'Addresses' );
		await expect(
			page.locator( '.woocommerce-MyAccount-navigation-link >> nth=4' )
		).toContainText( 'Account details' );
		await expect(
			page.locator( '.woocommerce-MyAccount-navigation-link >> nth=5' )
		).toContainText( 'Logout' );
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
