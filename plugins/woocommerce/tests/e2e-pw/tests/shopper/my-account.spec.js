const { test, expect } = require( '@playwright/test' );
const { customer, getTextForLanguage } = require( '../../test-data/data' );

const pages = getTextForLanguage()['pages'];

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
		).toContainText( getTextForLanguage()['Dashboard'] );
		await expect(
			page.locator( '.woocommerce-MyAccount-navigation-link--orders' )
		).toContainText( getTextForLanguage()['Orders'] );
		await expect(
			page.locator( '.woocommerce-MyAccount-navigation-link--downloads' )
		).toContainText( getTextForLanguage()['Downloads']  );
		await expect(
			page.locator(
				'.woocommerce-MyAccount-navigation-link--edit-address'
			)
		).toContainText( getTextForLanguage()['Addresses'] );
		await expect(
			page.locator(
				'.woocommerce-MyAccount-navigation-link--edit-account'
			)
		).toContainText( getTextForLanguage()['Accountdetails'] );
		await expect(
			page.locator(
				'.woocommerce-MyAccount-navigation-link--customer-logout'
			)
		).toContainText( getTextForLanguage()['Logout'] );
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
