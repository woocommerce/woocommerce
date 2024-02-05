/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-playwright-utils';

/**
 * Internal dependencies
 */
import { reviews } from '../../test-data/data/data';

const blockData = {
	name: 'woocommerce/reviews-by-product',
	selectors: {
		frontend: {
			firstReview:
				'.wc-block-review-list-item__item:first-child .wc-block-review-list-item__text p',
		},
	},
};

test.describe( `${ blockData.name } Block`, () => {
	test( 'block can be inserted and it successfully renders a review in the editor and the frontend', async ( {
		admin,
		editor,
		page,
		editorUtils,
	} ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: blockData.name } );
		const productCheckbox = page.getByLabel( 'Hoodie, has 2 reviews' );
		productCheckbox.check();
		await expect( productCheckbox ).toBeChecked();
		const doneButton = page.getByRole( 'button', { name: 'Done' } );
		await doneButton.click();

		await expect( page.getByText( reviews[ 0 ].review ) ).toBeVisible();

		await editorUtils.publishAndVisitPost();

		await expect( page.getByText( reviews[ 0 ].review ) ).toBeVisible();
	} );

	test( 'can change sort order in the frontend', async ( {
		page,
		frontendUtils,
	} ) => {
		await page.goto( '/reviews-by-product/' );

		const block = await frontendUtils.getBlockByName( blockData.name );
		let firstReview;
		firstReview = block.locator( blockData.selectors.frontend.firstReview );
		await expect( firstReview ).toHaveText( reviews[ 1 ].review );

		const select = page.getByLabel( 'Order by' );
		select.selectOption( 'Highest rating' );

		firstReview = block.locator( blockData.selectors.frontend.firstReview );
		await expect( firstReview ).toHaveText( reviews[ 0 ].review );
	} );
} );
