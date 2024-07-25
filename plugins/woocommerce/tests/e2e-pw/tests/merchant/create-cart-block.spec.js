const { test: baseTest, expect } = require( '../../fixtures/fixtures' );
const {
	goToPageEditor,
	fillPageTitle,
	insertBlock,
	transformIntoBlocks,
	publishPage,
} = require( '../../utils/editor' );
const { getInstalledWordPressVersion } = require( '../../utils/wordpress' );

const test = baseTest.extend( {
	storageState: process.env.ADMINSTATE,
	testPageTitlePrefix: 'Transformed cart',
} );

test.describe(
	'Transform Classic Cart To Cart Block',
	{ tag: [ '@gutenberg', '@services' ] },
	() => {
		test( 'can transform classic cart to cart block', async ( {
			page,
			testPage,
		} ) => {
			await goToPageEditor( { page } );

			await fillPageTitle( page, testPage.title );
			const wordPressVersion = await getInstalledWordPressVersion();
			await insertBlock( page, 'Classic Cart', wordPressVersion );
			await transformIntoBlocks( page );
			await publishPage( page, testPage.title );

			// go to frontend to verify transformed cart block
			await page.goto( testPage.slug );
			await expect(
				page.getByRole( 'heading', { name: testPage.title } )
			).toBeVisible();
			await expect(
				page.getByRole( 'heading', {
					name: 'Your cart is currently empty!',
				} )
			).toBeVisible();
			await expect(
				page.getByRole( 'link', { name: 'Browse store' } )
			).toBeVisible();
		} );
	}
);
