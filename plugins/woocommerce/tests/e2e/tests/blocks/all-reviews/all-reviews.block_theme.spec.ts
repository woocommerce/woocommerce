/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { allReviews } from '../../../test-data/blocks/data/data';

const BLOCK_NAME = 'woocommerce/all-reviews';

const latestReview = allReviews[ allReviews.length - 1 ];

const highestRating = [ ...allReviews ].sort(
	( a, b ) => b.rating - a.rating
)[ 0 ];

const lowestRating = [ ...allReviews ].sort(
	( a, b ) => a.rating - b.rating
)[ 0 ];

test.describe( `${ BLOCK_NAME } Block`, () => {
	test.beforeEach( async ( { admin, editor } ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: BLOCK_NAME } );
	} );

	test( 'block can be inserted and sorts reviews in the frontend', async ( {
		frontendUtils,
		editor,
	} ) => {
		await expect(
			editor.canvas.getByText( allReviews[ 0 ].review )
		).toBeVisible();
		await editor.publishAndVisitPost();

		const block = await frontendUtils.getBlockByName( BLOCK_NAME );
		const reviews = block.locator(
			'.wc-block-components-review-list-item__text'
		);
		const select = block.getByLabel( 'Order by' );
		await test.step( 'Most recent by default', async () => {
			await expect( reviews.first() ).toHaveText( latestReview.review );
		} );

		await test.step( 'Highest rating', async () => {
			await select.selectOption( 'Highest rating' );
			await expect( reviews.first() ).toHaveText( highestRating.review );
		} );
		await test.step( 'Lowest rating', async () => {
			await select.selectOption( 'Lowest rating' );
			await expect( reviews.first() ).toHaveText( lowestRating.review );
		} );
	} );
} );
