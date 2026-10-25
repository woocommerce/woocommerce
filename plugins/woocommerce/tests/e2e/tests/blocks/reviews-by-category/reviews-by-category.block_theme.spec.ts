/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { allReviews, hoodieReviews } from '../../../test-data/blocks/data/data';

const BLOCK_NAME = 'woocommerce/reviews-by-category';

test.describe( `${ BLOCK_NAME } Block`, () => {
	test( 'block can be inserted and it successfully renders a review in the editor and the frontend', async ( {
		page,
		admin,
		editor,
	} ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: BLOCK_NAME } );

		const blockLocator = await editor.getBlockByName( BLOCK_NAME );
		const categoryCheckbox = blockLocator.getByRole( 'checkbox', {
			name: 'Clothing',
			exact: true,
		} );
		await categoryCheckbox.click();
		await expect( categoryCheckbox ).toBeChecked();

		await blockLocator.getByRole( 'button', { name: 'Done' } ).click();
		await expect(
			editor.canvas.getByText( hoodieReviews[ 0 ].review )
		).toBeVisible();

		await editor.publishAndVisitPost();

		await expect(
			page
				.locator( '.wp-block-woocommerce-reviews-by-category' )
				.getByText( hoodieReviews[ 0 ].review )
		).toBeVisible();
	} );

	test( 'Category offset persists into frontend review results', async ( {
		page,
		admin,
		frontendUtils,
		editor,
	} ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: BLOCK_NAME } );
		const blockLocator = await editor.getBlockByName( BLOCK_NAME );
		await blockLocator
			.getByRole( 'checkbox', {
				name: 'Clothing',
				exact: true,
			} )
			.check();
		await blockLocator
			.getByRole( 'checkbox', { name: /Accessories/ } )
			.check();
		await blockLocator.getByRole( 'button', { name: 'Done' } ).click();

		await editor.openDocumentSettingsSidebar();
		const sidebarSettings = page.getByRole( 'region', {
			name: 'Editor settings',
		} );
		await sidebarSettings
			.getByRole( 'spinbutton', { name: 'Number of reviews' } )
			.fill( '10' );
		await sidebarSettings
			.getByRole( 'spinbutton', { name: 'Offset' } )
			.fill( '1' );

		await expect(
			editor.canvas.getByText( allReviews[ 0 ].review )
		).toBeVisible();
		await expect(
			editor.canvas.getByText( allReviews[ 1 ].review )
		).toBeVisible();
		await expect(
			editor.canvas.getByText( allReviews[ 2 ].review )
		).toBeVisible();
		await expect(
			editor.canvas.getByText( allReviews[ 3 ].review )
		).toBeVisible();
		await expect(
			editor.canvas.getByText( allReviews[ 4 ].review )
		).toBeHidden();

		await editor.publishAndVisitPost();

		const block = await frontendUtils.getBlockByName( BLOCK_NAME );
		await expect( block.getByText( allReviews[ 0 ].review ) ).toBeVisible();
		await expect( block.getByText( allReviews[ 1 ].review ) ).toBeVisible();
		await expect( block.getByText( allReviews[ 2 ].review ) ).toBeVisible();
		await expect( block.getByText( allReviews[ 3 ].review ) ).toBeVisible();
		await expect( block.getByText( allReviews[ 4 ].review ) ).toBeHidden();
	} );
} );
