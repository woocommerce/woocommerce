const { UPDATE_WC, USER_KEY, USER_SECRET } = process.env;
const { test: setup, expect } = require( '@playwright/test' );
const fs = require( 'fs' );
const { downloadWooCommerceRelease } = require( './utils/plugin-utils' );
const pluginEndpoint = '/wp-json/wp/v2/plugins/woocommerce/woocommerce';

let zipPath;

setup( `Setup remote test site`, async ( { page, request } ) => {
	setup.setTimeout( 5 * 60 * 1000 );

	await setup.step( `Download WooCommerce build zip`, async () => {
		zipPath = await downloadWooCommerceRelease( { request } );
	} );

	await setup.step( 'Login to wp-admin', async () => {
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

	const installed = await setup.step(
		`See if there's a WooCommerce plugin installed`,
		async () => {
			const response = await request.get( pluginEndpoint );
			const isOK = response.ok();
			const status = response.status();

			// Fast-fail if response was neither OK nor 404.
			expect( isOK || status === 404 ).toEqual( true );

			return isOK;
		}
	);

	await setup.step(
		`Deactivate currently installed WooCommerce version`,
		async () => {
			if ( ! installed ) {
				return;
			}

			const options = {
				data: {
					status: 'inactive',
				},
			};
			const response = await request.put( pluginEndpoint, options );
			expect( response.ok() ).toBeTruthy();
		}
	);

	await setup.step(
		`Delete currently installed WooCommerce version`,
		async () => {
			if ( ! installed ) {
				return;
			}

			const response = await request.delete( pluginEndpoint );
			expect( response.ok() ).toBeTruthy();
		}
	);

	await setup.step( `Install WooCommerce ${ UPDATE_WC }`, async () => {
		const Upload_Plugin = 'Upload Plugin';
		const Plugin_zip_file = 'Plugin zip file';
		const Install_Now = 'Install Now';
		const Activate_Plugin = 'Activate Plugin';
		const timeout = 3 * 60 * 1000;

		await page.goto( '/wp-admin/plugin-install.php' );
		await page.getByRole( 'button', { name: Upload_Plugin } ).click();
		await page.getByLabel( Plugin_zip_file ).setInputFiles( zipPath );
		await page.getByRole( 'button', { name: Install_Now } ).click();
		await expect(
			page.getByRole( 'link', { name: Activate_Plugin } )
		).toBeVisible( { timeout } );
	} );

	await setup.step( `Activate WooCommerce`, async () => {
		const options = {
			data: {
				status: 'active',
			},
		};
		const response = await request.put( pluginEndpoint, options );
		expect( response.ok() ).toBeTruthy();
	} );

	await setup.step( `Verify WooCommerce version was installed`, async () => {
		const response = await request.get( pluginEndpoint );
		const { status, version } = await response.json();
		expect( status ).toEqual( 'active' );
		expect( version ).toEqual( UPDATE_WC );
	} );

	await setup.step( `Verify WooCommerce database version`, async () => {
		const response = await request.get( '/wp-json/wc/v3/system_status' );
		const { database } = await response.json();
		const { wc_database_version } = database;
		const [ major, minor ] = UPDATE_WC.split( '.' );
		const pattern = new RegExp( `^${ major }\.${ minor }` );
		expect( wc_database_version ).toMatch( pattern );
	} );

	await setup.step( `Delete zip`, async () => {
		fs.unlinkSync( zipPath );
	} );
} );
