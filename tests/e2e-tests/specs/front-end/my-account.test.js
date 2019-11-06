/**
 * @format
 */

/**
 * External dependencies
 */
import { switchUserToTest } from '@wordpress/e2e-test-utils';

describe( 'My account page', () => {
	beforeAll( async () => {
		await switchUserToTest();
	} );

	it( 'allows customer to login', async () => {
		await expect( page ).toMatch( 'Hello' );
		await expect( page ).toMatchElement( '.woocommerce-MyAccount-navigation-link', { text: 'Dashboard' } );
		await expect( page ).toMatchElement( '.woocommerce-MyAccount-navigation-link', { text: 'Orders' } );
		await expect( page ).toMatchElement( '.woocommerce-MyAccount-navigation-link', { text: 'Downloads' } );
		await expect( page ).toMatchElement( '.woocommerce-MyAccount-navigation-link', { text: 'Addresses' } );
		await expect( page ).toMatchElement( '.woocommerce-MyAccount-navigation-link', { text: 'Account details' } );
		await expect( page ).toMatchElement( '.woocommerce-MyAccount-navigation-link', { text: 'Logout' } );
	} );

	it( 'allows customer to see orders', async () => {
		expect( page ).toClick( '.woocommerce-MyAccount-navigation-link', { text: 'Orders' } );
		await page.waitForNavigation( { waitUntil: 'networkidle0' } );

		expect( page.url() ).toMatch( '/my-account/orders' );
		await expect( page ).toMatchElement( 'h1', { text: 'Orders' } );
	} );

	it( 'allows customer to see downloads', async () => {
		expect( page ).toClick( '.woocommerce-MyAccount-navigation-link', { text: 'Downloads' } )
		await page.waitForNavigation( { waitUntil: 'networkidle0' } );

		expect( page.url() ).toMatch( '/my-account/downloads' );
		await expect( page ).toMatchElement( 'h1', { text: 'Downloads' } );
	} );

	it( 'allows customer to see addresses', async () => {
		expect( page ).toClick( '.woocommerce-MyAccount-navigation-link', { text: 'Addresses' } );
		await page.waitForNavigation( { waitUntil: 'networkidle0' } );

		expect( page.url() ).toMatch( '/my-account/edit-address' );
		await expect( page ).toMatchElement( 'h1', { text: 'Addresses' } );
	} );

	it( 'allows customer to see account details', async () => {
		expect( page ).toClick( '.woocommerce-MyAccount-navigation-link', { text: 'Account details' } );
		await page.waitForNavigation( { waitUntil: 'networkidle0' } );

		expect( page.url() ).toMatch( '/my-account/edit-account' );
		await expect( page ).toMatchElement( 'h1', { text: 'Account details' } );
	} );
} );
