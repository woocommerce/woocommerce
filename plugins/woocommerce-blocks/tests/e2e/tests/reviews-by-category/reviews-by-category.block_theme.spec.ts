/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-playwright-utils';

/**
 * Internal dependencies
 */
import { allReviews, hoodieReviews } from '../../test-data/data/data';

const latestReview = allReviews[ allReviews.length - 1 ];

const highestRating = [ ...allReviews ].sort(
	( a, b ) => b.rating - a.rating
)[ 0 ];

const lowestRating = [ ...allReviews ].sort(
	( a, b ) => a.rating - b.rating
)[ 0 ];

const BLOCK_NAME = 'woocommerce/reviews-by-category';

test.describe( `${ BLOCK_NAME } Block`, () => {
	test.beforeEach( async ( { admin, editor } ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: BLOCK_NAME } );
	} );

	test( 'block can be inserted and it successfully renders a review in the editor and the frontend', async ( {
		page,
		editorUtils,
	} ) => {
		const categoryCheckbox = page.getByLabel( 'Clothing' );
		await categoryCheckbox.check();
		await expect( categoryCheckbox ).toBeChecked();
		const doneButton = page.getByRole( 'button', { name: 'Done' } );
		await doneButton.click();

		await expect(
			page.getByText( hoodieReviews[ 0 ].review )
		).toBeVisible();

		await editorUtils.publishAndVisitPost();

		await expect(
			page.getByText( hoodieReviews[ 0 ].review )
		).toBeVisible();
	} );

	test( 'sorts by most recent review by default and can sort by highest rating', async ( {
		page,
		frontendUtils,
		editorUtils,
	} ) => {
		await editorUtils.publishAndVisitPost();

		const block = await frontendUtils.getBlockByName( BLOCK_NAME );

		const reviews = block.locator(
			'.wc-block-components-review-list-item__text'
		);

		await expect( reviews.first() ).toHaveText( latestReview.review );

		const select = page.getByLabel( 'Order by' );
		await select.selectOption( 'Highest rating' );

		await expect( reviews.first() ).toHaveText( highestRating.review );
	} );

	test( 'can sort by lowest rating', async ( {
		page,
		frontendUtils,
		editorUtils,
	} ) => {
		await editorUtils.publishAndVisitPost();

		const block = await frontendUtils.getBlockByName( BLOCK_NAME );

		const reviews = block.locator(
			'.wc-block-components-review-list-item__text'
		);

		const select = page.getByLabel( 'Order by' );
		await select.selectOption( 'Lowest rating' );

		await expect( reviews.first() ).toHaveText( lowestRating.review );
	} );
} );
