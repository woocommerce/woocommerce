const { test, expect, request } = require( '@playwright/test' );
const { features } = require( '../../utils' );
const { activateTheme } = require( '../../utils/themes' );

test.describe( 'Store owner can view Assembler Hub for store customization', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async ( { baseURL } ) => {
		await features.set_feature_flag(
			request,
			baseURL,
			'customize-store',
			true
		);

		// Need a block enabled theme to test
		await activateTheme( 'twentytwentythree' );
	} );

	test.afterAll( async ( { baseURL } ) => {
		await features.set_feature_flag(
			request,
			baseURL,
			'customize-store',
			false
		);

		// Reset theme back to twentynineteen
		await activateTheme( 'twentynineteen' );
	} );

	test( 'Can view the Assembler Hub page', async ( { page } ) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-admin&path=%2Fcustomize-store%2Fassembler-hub'
		);
		await page.waitForSelector( `h1:text("Let's get creative")`, {
			timeout: 5000,
		} );
	} );
} );
