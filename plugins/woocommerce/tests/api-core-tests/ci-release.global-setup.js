const { UPDATE_WC, USER_KEY, USER_SECRET } = process.env;
const { test, expect } = require( '@playwright/test' );
const path = require( 'path' );
const fs = require( 'fs' );

const zipPath = path.resolve( 'tmp', 'woocommerce.zip' );
const downloadURL = `https://github.com/woocommerce/woocommerce/releases/download/${ UPDATE_WC }/woocommerce.zip`;

test( `Setup remote test site`, async ( { page, request } ) => {
	await test.step( `Download WooCommerce build zip`, async () => {
		const response = await request.get( downloadURL );
		expect( response.ok() ).toBeTruthy();
		const body = await response.body();
		fs.mkdirSync( 'tmp', { recursive: true } );
		fs.writeFileSync( zipPath, body );
	} );

	await test.step( 'Login to wp-admin', async () => {
		const Username = 'Username or Email Address';
		const Password = 'Password';
		const Log_In = 'Log In';
		const Dashboard = 'Dashboard';

		// Need to wait until network idle. Otherwise, Password field gets auto-cleared after typing password in.
		await page.goto( '/wp-admin', { waitUntil: 'networkidle' } );
		await page.getByLabel( Username ).fill( USER_KEY );
		await page.getByLabel( Password, { exact: true } ).fill( USER_SECRET );
		await page.getByRole( 'button', { name: Log_In } ).click();
		await expect(
			page
				.locator( '#menu-dashboard' )
				.getByRole( 'link', { name: Dashboard } )
		).toBeVisible();
	} );

	await test.step( `Deactivate currently installed WooCommerce version`, async () => {
		const response = await request.put(
			'/wp-json/wp/v2/plugins/woocommerce/woocommerce',
			{
				data: {
					status: 'inactive',
				},
			}
		);
		expect( response.ok() ).toBeTruthy();
	} );

	await test.step( `Delete currently installed WooCommerce version`, async () => {
		const response = await request.delete(
			'/wp-json/wp/v2/plugins/woocommerce/woocommerce'
		);
		expect( response.ok() ).toBeTruthy();
	} );

	await test.step( `Install WooCommerce ${ UPDATE_WC }`, async () => {
		const Upload_Plugin = 'Upload Plugin';
		const Plugin_zip_file = 'Plugin zip file';
		const Install_Now = 'Install Now';
		const Activate_Plugin = 'Activate Plugin';

		await page.goto( '/wp-admin/plugin-install.php' );
		await page.getByRole( 'button', { name: Upload_Plugin } ).click();
		await page.getByLabel( Plugin_zip_file ).setInputFiles( zipPath );
		await page.getByRole( 'button', { name: Install_Now } ).click();
		const uploadResponse = await page.waitForResponse(
			'**/wp-admin/update.php?action=upload-plugin'
		);;
		expect( uploadResponse.ok() ).toBeTruthy();
		await expect(
			page.getByRole( 'link', { name: Activate_Plugin } )
		).toBeVisible();
	} );

	await test.step( `Activate WooCommerce`, async () => {
		const response = await request.put(
			'/wp-json/wp/v2/plugins/woocommerce/woocommerce',
			{
				data: {
					status: 'active',
				},
			}
		);
		expect( response.ok() ).toBeTruthy();
	} );

	await test.step( `Verify WooCommerce version was installed`, async () => {
		const response = await request.get(
			'/wp-json/wp/v2/plugins/woocommerce/woocommerce'
		);
		const { status, version } = await response.json();
		expect( status ).toEqual( 'active' );
		expect( version ).toEqual( UPDATE_WC );
	} );

	await test.step( `Verify WooCommerce database version`, async () => {
		const response = await request.get( '/wp-json/wc/v3/system_status' );
		const { database } = await response.json();
		const { wc_database_version } = database;
		const [major, minor] = UPDATE_WC.split( '.' );
		const pattern = new RegExp( `^${ major }\.${ minor }` );
		expect( wc_database_version ).toMatch( pattern );
	} );

	await test.step( `Delete zip`, async () => {
		fs.unlinkSync( zipPath );
	} );
} );
