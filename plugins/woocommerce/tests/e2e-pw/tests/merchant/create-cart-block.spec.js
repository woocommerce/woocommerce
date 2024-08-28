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

			// Dismiss "Choose a pattern" if present (Pressable)
			// eslint-disable-next-line playwright/no-conditional-in-test
			if (
				await page.getByLabel( 'Close', { exact: true } ).isVisible()
			) {
				await page.getByLabel( 'Close', { exact: true } ).click();
			}

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
			try {
				await expect(
					page.getByRole( 'link', { name: 'Browse store' } )
				).toBeVisible();
			} catch ( e ) {
				// Cover the case when we run tests on Pressable
				// eslint-disable-next-line jest/no-try-expect
				await expect(
					page.getByRole( 'heading', { name: 'New in store' } )
				).toBeVisible();
			}
		} );
	}
);
