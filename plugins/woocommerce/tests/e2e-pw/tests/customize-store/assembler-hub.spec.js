const { test, expect, request } = require( '@playwright/test' );
const { features } = require( '../../utils' );
const { activateTheme } = require( '../../utils/themes' );

const ASSEMBLER_HUB_URL =
	'/wp-admin/admin.php?page=wc-admin&path=%2Fcustomize-store%2Fassembler-hub';

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
		await features.reset_feature_flags( request, baseURL );

		// Reset theme back to twentynineteen
		await activateTheme( 'twentynineteen' );
	} );

	test( 'Can view the Assembler Hub page', async ( { page } ) => {
		await page.goto( ASSEMBLER_HUB_URL );
		const locator = page.locator( 'h1:visible' );
		await expect( locator ).toHaveText( "Let's get creative" );
	} );

	test( 'Visiting change header should show a list of block patterns to choose from', async ( {
		page,
	} ) => {
		await page.goto( ASSEMBLER_HUB_URL );
		await page.click( 'text=Change your header' );

		const locator = page.locator(
			'.block-editor-block-patterns-list__list-item'
		);

		await expect( locator ).toHaveCount( 4 );
	} );
} );
