/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-playwright-utils';

const blockData = {
	name: 'woocommerce/reviews-by-category',
	selectors: {
		frontend: {
			firstReview:
				'.wc-block-review-list-item__item:first-child .wc-block-review-list-item__text p',
		},
	},
};

test.describe( `${ blockData.name } Block`, () => {
	test( 'renders a review in the editor and the frontend', async ( {
		admin,
		editor,
		page,
		editorUtils,
	} ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: blockData.name } );
		const categoryCheckbox = page.getByLabel( 'Clothing' );
		categoryCheckbox.check();
		await expect( categoryCheckbox ).toBeChecked();
		const doneButton = page.getByRole( 'button', { name: 'Done' } );
		await doneButton.click();

		await expect( page.getByText( 'Nice album!' ) ).toBeVisible();

		await editorUtils.publishAndVisitPost();

		await expect( page.getByText( 'Nice album!' ) ).toBeVisible();
	} );

	test( 'can change sort order in the frontend', async ( {
		admin,
		editor,
		page,
		frontendUtils,
		editorUtils,
	} ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: blockData.name } );
		const categoryCheckbox = page.getByLabel( 'Clothing' );
		categoryCheckbox.check();
		await expect( categoryCheckbox ).toBeChecked();
		const doneButton = page.getByRole( 'button', { name: 'Done' } );
		await doneButton.click();
		await editorUtils.publishAndVisitPost();

		const block = await frontendUtils.getBlockByName( blockData.name );
		let firstReview;
		firstReview = block.locator( blockData.selectors.frontend.firstReview );
		await expect( firstReview ).toHaveText( 'Not bad.' );

		const select = page.getByLabel( 'Order by' );
		select.selectOption( 'Highest rating' );

		firstReview = block.locator( blockData.selectors.frontend.firstReview );
		await expect( firstReview ).toHaveText( 'Nice album!' );
	} );
} );
