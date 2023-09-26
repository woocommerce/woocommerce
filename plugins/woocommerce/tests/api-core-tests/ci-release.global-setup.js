const { UPDATE_WC, USER_KEY, USER_SECRET } = process.env;
const { test, expect } = require( '@playwright/test' );
const path = require( 'path' );
const fs = require( 'fs' );

const zipPath = path.resolve( 'tmp', 'woocommerce.zip' );
const nightlyZipURL =
	'https://github.com/woocommerce/woocommerce/releases/download/nightly/woocommerce-trunk-nightly.zip';
const releaseZipURL = `https://github.com/woocommerce/woocommerce/releases/download/${ UPDATE_WC }/woocommerce.zip`;
const downloadURL = UPDATE_WC === 'nightly' ? nightlyZipURL : releaseZipURL;

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

		await page.goto( '/wp-admin' );
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
		const Deactivate_WooCommerce = 'Deactivate WooCommerce';
		const Delete_WooCommerce = 'Delete WooCommerce';

		await page.goto( '/wp-admin/plugins.php' );
		await page.getByLabel( Deactivate_WooCommerce ).click();
		await expect( page.getByLabel( Delete_WooCommerce ) ).toBeVisible();
	} );

	await test.step( `Delete currently installed WooCommerce version`, async () => {
		const Delete_WooCommerce = 'Delete WooCommerce';
		const WooCommerce_was_successfully_deleted =
			'WooCommerce was successfully deleted.';

		page.on( 'dialog', ( dialog ) => dialog.accept() );
		await page.getByLabel( Delete_WooCommerce ).click();
		await expect(
			page.getByText( WooCommerce_was_successfully_deleted )
		).toBeVisible();
		await expect(
			page.getByRole( 'rowheader', { name: 'Select WooCommerce' } )
		).not.toBeVisible();
	} );

	await test.step( `Install WooCommerce version to be tested`, async () => {
		await page.goto( '/wp-admin/plugin-install.php' );
		await page.getByRole( 'button', { name: 'Upload Plugin' } ).click();
		await page.getByLabel( 'Plugin zip file' ).setInputFiles( zipPath );
		const responsePromise = page.waitForResponse(
			'**/wp-admin/update.php?action=upload-plugin'
		);
		await page.getByRole( 'button', { name: 'Install Now' } ).click();
		const uploadResponse = await responsePromise;
		expect( uploadResponse.ok() ).toBeTruthy();
		await expect(
			page.getByRole( 'link', { name: 'Activate Plugin' } )
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

	await test.step( `Delete zip`, async () => {
		fs.unlinkSync( zipPath );
	} );
} );
