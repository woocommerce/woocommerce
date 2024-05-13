/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-playwright-utils';

/**
 * Internal dependencies
 */
import { allReviews } from '../../test-data/data/data';

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

	test( 'block can be inserted and it sorts reviews by most recent by default', async ( {
		frontendUtils,
		page,
		editorUtils,
	} ) => {
		await expect( page.getByText( allReviews[ 0 ].review ) ).toBeVisible();

		await editorUtils.publishAndVisitPost();

		const block = await frontendUtils.getBlockByName( BLOCK_NAME );
		const reviews = block.locator(
			'.wc-block-components-review-list-item__text'
		);

		await expect( reviews.first() ).toHaveText( latestReview.review );
	} );

	test( 'can sort by highest rating in the frontend', async ( {
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

	test( 'can sort by lowest rating in the frontend', async ( {
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
		await select.selectOption( 'Lowest rating' );

		await expect( reviews.first() ).toHaveText( lowestRating.review );
	} );
} );
