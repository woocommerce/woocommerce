/**
 * @format
 */

/**
 * External dependencies
 */
import { switchUserToTest } from '@wordpress/e2e-test-utils';

const customerUsername = process.env.WP_USERNAME;

describe( 'My account page', () => {
	beforeAll( async () => {
		await switchUserToTest();
	} );

	it( 'allows customer to login', async () => {
		await expect( page ).toMatch( 'Hello ' + customerUsername );
		await expect( page ).toMatchElement( '.woocommerce-MyAccount-navigation-link', { text: 'Dashboard' } );
		await expect( page ).toMatchElement( '.woocommerce-MyAccount-navigation-link', { text: 'Orders' } );
	} );

	it( 'allows customer to see orders', async () => {
		await Promise.all( [
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			expect( page ).toClick( '.woocommerce-MyAccount-navigation-link', { text: 'Orders' } ),
		] );

		expect( page.url() ).toMatch( '/my-account/orders' );
		await expect( page ).toMatchElement( 'h1', { text: 'Orders' } );
	} );

	it( 'allows customer to see downloads', async () => {
		await Promise.all( [
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			expect( page ).toClick( '.woocommerce-MyAccount-navigation-link', { text: 'Downloads' } ),
		] );

		expect( page.url() ).toMatch( '/my-account/downloads' );
		await expect( page ).toMatchElement( 'h1', { text: 'Downloads' } );
	} );

	it( 'allows customer to edit addresses', async () => {
		await Promise.all( [
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			expect( page ).toClick( '.woocommerce-MyAccount-navigation-link', { text: 'Addresses' } ),
		] );

		expect( page.url() ).toMatch( '/my-account/edit-address' );
		await expect( page ).toMatchElement( 'h1', { text: 'Addresses' } );
	} );

	it( 'allows customer to edit account details', async () => {
		await Promise.all( [
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			expect( page ).toClick( '.woocommerce-MyAccount-navigation-link', { text: 'Account details' } ),
		] );

		expect( page.url() ).toMatch( '/my-account/edit-account' );
		await expect( page ).toMatchElement( 'h1', { text: 'Account details' } );
	} );
} );
