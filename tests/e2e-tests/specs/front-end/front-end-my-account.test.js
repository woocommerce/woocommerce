/**
 * @format
 */

/**
 * Internal dependencies
 */
import { CustomerFlow, StoreOwnerFlow } from '../../utils/flows';

describe( 'My Account Page', () => {
	it( 'allows customer to login', async () => {
		await StoreOwnerFlow.logout();
		await CustomerFlow.login();
		await expect( page ).toMatch( 'Hello' );
		await expect( page ).toMatchElement( '.woocommerce-MyAccount-navigation-link', { text: 'Dashboard' } );
		await expect( page ).toMatchElement( '.woocommerce-MyAccount-navigation-link', { text: 'Orders' } );
		await expect( page ).toMatchElement( '.woocommerce-MyAccount-navigation-link', { text: 'Downloads' } );
		await expect( page ).toMatchElement( '.woocommerce-MyAccount-navigation-link', { text: 'Addresses' } );
		await expect( page ).toMatchElement( '.woocommerce-MyAccount-navigation-link', { text: 'Account details' } );
		await expect( page ).toMatchElement( '.woocommerce-MyAccount-navigation-link', { text: 'Logout' } );
	} );

	it( 'allows customer to see orders', async () => {
		await CustomerFlow.goToOrders();
		await expect( page.url() ).toMatch( 'my-account/orders' );
		await expect( page ).toMatchElement( 'h1', { text: 'Orders' } );
	} );

	it( 'allows customer to see downloads', async () => {
		await CustomerFlow.goToDownloads();
		expect( page.url() ).toMatch( 'my-account/downloads' );
		await expect( page ).toMatchElement( 'h1', { text: 'Downloads' } );
	} );

	it( 'allows customer to see addresses', async () => {
		await CustomerFlow.goToAddresses();
		expect( page.url() ).toMatch( 'my-account/edit-address' );
		await expect( page ).toMatchElement( 'h1', { text: 'Addresses' } );
	} );

	it( 'allows customer to see account details', async () => {
		await CustomerFlow.goToAccountDetails();
		expect( page.url() ).toMatch( 'my-account/edit-account' );
		await expect( page ).toMatchElement( 'h1', { text: 'Account details' } );
	} );
} );
