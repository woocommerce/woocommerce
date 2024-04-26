const { test: baseTest, expect } = require( '../../fixtures/fixtures' );
const {
	goToPageEditor,
	fillPageTitle,
	insertBlock,
	transformIntoBlocks,
	publishPage,
} = require( '../../utils/editor' );
const uuid = require( 'uuid' );

baseTest.describe( 'Transform Classic Cart To Cart Block', () => {
	const test = baseTest.extend( {
		storageState: process.env.ADMINSTATE,
		testPageTitle: `Transformed cart ${ uuid.v1() }`,
	} );

	test( 'can transform classic cart to cart block', async ( {
		page,
		testPage,
	} ) => {
		await goToPageEditor( { page } );

		await fillPageTitle( page, testPage.title );
		await insertBlock( page, 'Classic Cart' );
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
} );
