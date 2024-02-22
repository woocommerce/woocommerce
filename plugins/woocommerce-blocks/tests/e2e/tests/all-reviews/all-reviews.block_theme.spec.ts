/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-playwright-utils';

/**
 * Internal dependencies
 */
import { allReviews } from '../../test-data/data/data';

const blockData = {
	name: 'woocommerce/all-reviews',
	selectors: {
		frontend: {
			firstReview:
				'.wc-block-review-list-item__item:first-child .wc-block-review-list-item__text p',
		},
	},
};

const BLOCK_NAME = 'woocommerce/all-reviews';

const latestReview = allReviews[ allReviews.length - 1 ];

test.describe( `${ blockData.name } Block`, () => {
	test( 'block can be inserted and it successfully renders a review in the editor and the frontend', async ( {
		admin,
		editor,
		page,
		editorUtils,
	} ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: blockData.name } );

		await expect( page.getByText( allReviews[ 0 ].review ) ).toBeVisible();

		await editorUtils.publishAndVisitPost();

		await expect( page.getByText( allReviews[ 0 ].review ) ).toBeVisible();
	} );

	test( 'can change sort order in the frontend', async ( {
		page,
		frontendUtils,
		editorUtils,
		admin,
		editor,
	} ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: blockData.name } );
		await editorUtils.publishAndVisitPost();

		const block = await frontendUtils.getBlockByName( blockData.name );
		const reviews = block.locator(
			'.wc-block-components-review-list-item__text'
		);

		await expect( reviews.nth( 0 ) ).toHaveText( latestReview.review );

		const select = page.getByLabel( 'Order by' );
		select.selectOption( 'Highest rating' );

		const highestRating = allReviews.sort(
			( a, b ) => b.rating - a.rating
		)[ 0 ];

		await expect( reviews.nth( 0 ) ).toHaveText( highestRating.review );
	} );
} );
