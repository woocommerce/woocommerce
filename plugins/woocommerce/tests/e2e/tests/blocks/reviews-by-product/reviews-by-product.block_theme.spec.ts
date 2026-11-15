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

	test( 'can skip reviews with an offset in the editor and frontend', async ( {
		page,
		admin,
		frontendUtils,
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

		await editor.openDocumentSettingsSidebar();
		const sidebarSettings = page.getByRole( 'region', {
			name: 'Editor settings',
		} );

		await sidebarSettings
			.getByRole( 'spinbutton', { name: 'Offset' } )
			.fill( '1' );

		await expect(
			editor.canvas.getByText( hoodieReviews[ 0 ].review )
		).toBeVisible();
		await expect(
			editor.canvas.getByText( hoodieReviews[ 1 ].review )
		).toBeVisible();
		await expect(
			editor.canvas.getByText( hoodieReviews[ 2 ].review )
		).toBeHidden();

		await editor.publishAndVisitPost();

		const block = await frontendUtils.getBlockByName( BLOCK_NAME );

		await expect(
			block.getByText( hoodieReviews[ 0 ].review )
		).toBeVisible();
		await expect(
			block.getByText( hoodieReviews[ 1 ].review )
		).toBeVisible();
		await expect(
			block.getByText( hoodieReviews[ 2 ].review )
		).toBeHidden();
	} );
} );
