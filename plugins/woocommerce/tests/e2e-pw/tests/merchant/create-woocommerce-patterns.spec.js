const { test, expect } = require( '@playwright/test' );
const {
	goToPageEditor,
	fillPageTitle,
	insertBlock,
	getCanvas,
	publishPage,
} = require( '../../utils/editor' );

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
		await goToPageEditor( { page } );
		await fillPageTitle( page, wooPatternsPageTitle );

		for ( let i = 0; i < wooPatterns.length; i++ ) {
			await test.step( `Insert ${ wooPatterns[ i ].name } pattern`, async () => {
				await insertBlock( page, wooPatterns[ i ].name );

				await expect(
					page.getByLabel( 'Dismiss this notice' ).filter( {
						hasText: `Block pattern "${ wooPatterns[ i ].name }" inserted.`,
					} )
				).toBeVisible();

				const canvas = await getCanvas( page );
				await expect(
					canvas
						.getByRole( 'textbox' )
						.filter( { hasText: `${ wooPatterns[ i ].button }` } )
				).toBeVisible();
			} );
		}

		await publishPage( page, wooPatternsPageTitle );

		// check again added patterns after publishing
		const canvas = await getCanvas( page );
		for ( let i = 1; i < wooPatterns.length; i++ ) {
			await expect(
				canvas
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
