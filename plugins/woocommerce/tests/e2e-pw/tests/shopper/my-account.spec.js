const { test, expect } = require( '@playwright/test' );
const { customer } = require( '../../test-data/data' );

// const pages = [
// 	[ 'Orders', 'my-account/orders' ],
// 	[ 'Downloads', 'my-account/downloads' ],
// 	[ 'Addresses', 'my-account/edit-address' ],
// 	[ 'Account details', 'my-account/edit-account' ],
// ];//translate
const pages = [
	[ 'Pedidos', 'my-account/orders' ],
	[ 'Descargas', 'my-account/downloads' ],
	[ 'Dirección', 'my-account/edit-address' ],
	[ 'Detalles de la cuenta', 'my-account/edit-account' ],
];//translate

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
		// await expect(
		// 	page.locator( '.woocommerce-MyAccount-navigation-link--dashboard' )
		// ).toContainText( 'Dashboard' );//translate
		await expect(
			page.locator( '.woocommerce-MyAccount-navigation-link--dashboard' )
		).toContainText( 'Escritorio' );//translate
		// await expect(
		// 	page.locator( '.woocommerce-MyAccount-navigation-link--orders' )
		// ).toContainText( 'Orders' );//translate
		await expect(
			page.locator( '.woocommerce-MyAccount-navigation-link--orders' )
		).toContainText( 'Pedidos' );//translate
		// await expect(
		// 	page.locator( '.woocommerce-MyAccount-navigation-link--downloads' )
		// ).toContainText( 'Downloads' );//translate
		await expect(
			page.locator( '.woocommerce-MyAccount-navigation-link--downloads' )
		).toContainText( 'Descargas' );//translate
		// await expect(
		// 	page.locator(
		// 		'.woocommerce-MyAccount-navigation-link--edit-address'
		// 	)
		// ).toContainText( 'Addresses' );//translate
		await expect(
			page.locator(
				'.woocommerce-MyAccount-navigation-link--edit-address'
			)
		).toContainText( 'Direcciones' );//translate
		
		// await expect(
		// 	page.locator(
		// 		'.woocommerce-MyAccount-navigation-link--edit-account'
		// 	)
		// ).toContainText( 'Account details' );//translate
		await expect(
			page.locator(
				'.woocommerce-MyAccount-navigation-link--edit-account'
			)
		).toContainText( 'Detalles de la cuenta' );//translate
		// await expect(
		// 	page.locator(
		// 		'.woocommerce-MyAccount-navigation-link--customer-logout'
		// 	)
		// ).toContainText( 'Logout' );//translate
				await expect(
			page.locator(
				'.woocommerce-MyAccount-navigation-link--customer-logout'
			)
		).toContainText( 'Log out' );//translate
		
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
