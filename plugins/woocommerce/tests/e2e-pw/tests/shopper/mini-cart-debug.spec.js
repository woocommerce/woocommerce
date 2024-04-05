const { test, expect } = require( '@playwright/test' );
const { disableWelcomeModal } = require( '../../utils/editor' );

const miniCartPageTitle = `Mini Cart ${ Date.now() }`;

test.describe( 'Mini Cart block page', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test( 'customize font', async ( { page } ) => {
		// go to create a new page
		await page.goto( 'wp-admin/post-new.php?post_type=page' );

		await disableWelcomeModal( { page } );

		// add page title and mini cart block
		await page
			.getByRole( 'textbox', { name: 'Add title' } )
			.fill( miniCartPageTitle );
		await page.getByLabel( 'Add block' ).click();
		await page
			.getByLabel( 'Search for blocks and patterns' )
			.fill( '/mini cart' );
		await page
			.getByRole( 'option' )
			.filter( { hasText: 'Mini-Cart' } )
			.click();
		await expect( page.getByLabel( 'Block: Mini-Cart' ) ).toBeVisible();

		// customize mini cart block
		await page.getByLabel( 'Block: Mini-Cart' ).click();
		// display total price
		await page.getByLabel( 'Display total price' ).click();
		// open drawer when a product
		await page.getByLabel( 'Open drawer when adding' ).click();
		// open styles in the sidebar
		await page.getByLabel( 'Styles', { exact: true } ).click();

		await page.getByRole( 'button', { name: 'Font weight' } ).click();
		await page.getByRole( 'option' ).filter( { hasText: 'Black' } ).click();

		await expect(
			page.getByText( 'The editor has encountered an unexpected error' )
		).toBeHidden( { timeout: 3000 } );

		// publish created mini cart page
		await page
			.getByRole( 'button', { name: 'Publish', exact: true } )
			.click();
		await page
			.getByRole( 'region', { name: 'Editor publish' } )
			.getByRole( 'button', { name: 'Publish', exact: true } )
			.click();
		await expect(
			page.getByText( `${ miniCartPageTitle } is now live.` )
		).toBeVisible();
	} );
} );
