const { test, expect } = require( '@playwright/test' );
const { disableWelcomeModal } = require( '../../utils/editor' );

const wooPatternsPageTitle = `Insert Woo Patterns ${ Date.now() }`;
const wooPatternsPageSlug = wooPatternsPageTitle
	.replace( / /gi, '-' )
	.toLowerCase();

// some WooCommerce Patterns to use
const wooPatterns = [
	{
		name: 'Banner',
		button: 'Shop vinyl records',
	},
	{
		name: 'Discount Banner with Image',
		button: 'Shop now',
	},
	{
		name: 'Featured Category Focus',
		button: 'Shop prints',
	},
	{
		name: 'Featured Category Cover Image',
		button: 'Shop chairs',
	},
];

test.describe( 'Insert WooCommerce Patterns Into Page', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test( 'can insert WooCommerce patterns into page', async ( { page } ) => {
		// go to create a new page
		await page.goto( 'wp-admin/post-new.php?post_type=page' );

		await disableWelcomeModal( { page } );

		// fill page title
		await page
			.getByRole( 'textbox', { name: 'Add title' } )
			.fill( wooPatternsPageTitle );

		// add Woo Patterns and verify them as added into page
		for ( let i = 0; i < wooPatterns.length; i++ ) {
			// click title field for block inserter to show up
			await page.getByRole( 'textbox', { name: 'Add title' } ).click();

			// add pattern into page
			await page.getByLabel( 'Add block' ).click();
			await page
				.getByPlaceholder( 'Search', { exact: true } )
				.fill( wooPatterns[ i ].name );
			await page
				.getByRole( 'option', {
					name: wooPatterns[ i ].name,
					exact: true,
				} )
				.click();
			await expect(
				page.getByLabel( 'Dismiss this notice' ).filter( {
					hasText: `Block pattern "${ wooPatterns[ i ].name }" inserted.`,
				} )
			).toBeVisible();

			// verify added patterns into page
			await expect(
				page
					.getByRole( 'textbox' )
					.filter( { hasText: `${ wooPatterns[ i ].button }` } )
			).toBeVisible();
		}

		// save and publish the page
		await page
			.getByRole( 'button', { name: 'Publish', exact: true } )
			.click();
		await page
			.getByRole( 'region', { name: 'Editor publish' } )
			.getByRole( 'button', { name: 'Publish', exact: true } )
			.click();
		await expect(
			page.getByText( `${ wooPatternsPageTitle } is now live.` )
		).toBeVisible();

		// check again added patterns after publishing
		for ( let i = 1; i < wooPatterns.length; i++ ) {
			await expect(
				page
					.getByRole( 'textbox' )
					.filter( { hasText: `${ wooPatterns[ i ].button }` } )
			).toBeVisible();
		}

		// go to the frontend page to verify patterns
		await page.goto( wooPatternsPageSlug );
		await expect(
			page.getByRole( 'heading', { name: wooPatternsPageTitle } )
		).toBeVisible();

		// check some elements from added patterns
		for ( let i = 1; i < wooPatterns.length; i++ ) {
			await expect(
				page.getByText( `${ wooPatterns[ i ].button }` )
			).toBeVisible();
		}
	} );
} );
