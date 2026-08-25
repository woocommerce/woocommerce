/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { hoodieReviews } from '../../../test-data/blocks/data/data';

const BLOCK_NAME = 'woocommerce/reviews-by-product';

test.describe( `${ BLOCK_NAME } Block`, () => {
	test( 'block can be inserted and it successfully renders a review in the editor and the frontend', async ( {
		page,
		admin,
		editor,
	} ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: BLOCK_NAME } );

		const productCheckbox = editor.canvas.getByLabel(
			'Hoodie, has 3 reviews'
		);
		await productCheckbox.click();
		await expect( productCheckbox ).toBeChecked();

		await editor.canvas.getByRole( 'button', { name: 'Done' } ).click();
		await expect(
			editor.canvas.getByText( hoodieReviews[ 0 ].review )
		).toBeVisible();

		await editor.publishAndVisitPost();

		await expect(
			page
				.locator( '.wp-block-woocommerce-reviews-by-product' )
				.getByText( hoodieReviews[ 0 ].review )
		).toBeVisible();
	} );
} );
