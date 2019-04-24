/**
 * @format
 */

/** 
 * Internal dependencies
 */
const { StoreOwnerFlow } = require( './utils/flows' );

/** 
 * External dependencies
 */
const config = require( 'config' );

const baseUrl = config.get( 'url' );

describe( 'Check for functional WordPress installation', () => {
	beforeAll( async () => {
		await StoreOwnerFlow.login();
	} );

	it( 'Can log in to the dashboard', async () => {
		await expect( page.title() ).resolves.toMatch( 'Dashboard' );
	} );

	it( 'Updates WordPress database', async () => {
		await page.goto( baseUrl + 'wp-admin/upgrade.php?step=upgrade_db', {
			waitUntil: 'networkidle0',
		} );

		await expect( page.content() ).resolves.toMatch( '0' );
	} );

	it( 'Has a working theme', async() => {
		await page.goto( baseUrl + 'wp-admin/themes.php', {
			waitUntil: 'networkidle0',
		} );

		await expect( page.title() ).resolves.toMatch( 'Manage Themes' );
		await expect( page ).not.toMatchElement( 'div.notice, #message', { text: 'The active theme is broken.' } );
		await expect( page ).not.toMatchElement( 'div.notice, #message', { text: 'No themes found.' } );
		await expect( page ).not.toMatchElement( 'div.notice, #message', { text: 'ERROR:' } );
	} );
} );
