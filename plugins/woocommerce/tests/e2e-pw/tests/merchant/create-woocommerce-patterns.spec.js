const { test: baseTest, expect } = require( '../../fixtures/fixtures' );
const {
	goToPageEditor,
	fillPageTitle,
	insertBlock,
	getCanvas,
	publishPage,
	closeChoosePatternModal,
} = require( '../../utils/editor' );
const { getInstalledWordPressVersion } = require( '../../utils/wordpress' );

// some WooCommerce Patterns to use
const wooPatterns = [
	{
		name: 'Hero Product 3 Split',
		button: 'Shop now',
	},
	{
		name: 'Featured Category Cover Image',
		button: 'Shop chairs',
	},
];

const test = baseTest.extend( {
	storageState: process.env.ADMINSTATE,
	testPageTitlePrefix: 'Woocommerce Patterns',
} );

test.describe(
	'Add WooCommerce Patterns Into Page',
	{ tag: [ '@gutenberg', '@services' ] },
	() => {
		test( 'can insert WooCommerce patterns into page', async ( {
			page,
			testPage,
		} ) => {
			await goToPageEditor( { page } );

			await closeChoosePatternModal( { page } );

			await fillPageTitle( page, testPage.title );

			const wordPressVersion = await getInstalledWordPressVersion();

			for ( let i = 0; i < wooPatterns.length; i++ ) {
				await test.step( `Insert ${ wooPatterns[ i ].name } pattern`, async () => {
					await insertBlock(
						page,
						wooPatterns[ i ].name,
						wordPressVersion
					);

					await expect(
						page.getByLabel( 'Dismiss this notice' ).filter( {
							hasText: `Block pattern "${ wooPatterns[ i ].name }" inserted.`,
						} )
					).toBeVisible();

					const canvas = await getCanvas( page );
					await expect(
						canvas.getByRole( 'textbox' ).filter( {
							hasText: `${ wooPatterns[ i ].button }`,
						} )
					).toBeVisible();
				} );
			}

			await publishPage( page, testPage.title );

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
			await page.goto( testPage.slug );
			await expect(
				page.getByRole( 'heading', { name: testPage.title } )
			).toBeVisible();

			// check some elements from added patterns
			for ( let i = 1; i < wooPatterns.length; i++ ) {
				await expect(
					page.getByText( `${ wooPatterns[ i ].button }` )
				).toBeVisible();
			}
		} );
	}
);
