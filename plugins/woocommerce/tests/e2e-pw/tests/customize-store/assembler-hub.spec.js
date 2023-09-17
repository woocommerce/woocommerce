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
		await page.goto( ASSEMBLER_HUB_URL );
		await page.waitForSelector( `h1:text("Let's get creative")`, {
			timeout: 5000,
		} );
	} );

	test( 'Visiting change header should show a list of block patterns to choose from', async ( {
		page,
	} ) => {
		await page.goto( ASSEMBLER_HUB_URL );
		await page.waitForSelector( `h1:text("Let's get creative")`, {
			timeout: 5000,
		} );

		await page.click( 'text=Change your header' );

		// Wait for at least one element to appear
		await page.waitForSelector(
			'.block-editor-block-patterns-list__list-item',
			{ timeout: 5000 }
		);

		// Now query all elements and check their count
		const elements = await page.$$(
			'.block-editor-block-patterns-list__list-item'
		);
		expect( elements.length ).toBe( 4 );
	} );
} );
