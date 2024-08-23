/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */

const blockData = {
	name: 'Product Image Gallery',
	slug: 'woocommerce/product-image-gallery',
};

test.describe( `${ blockData.slug } Block`, () => {
	test( 'can be added in the Post Editor - Single Product Template', async ( {
		admin,
		editor,
		requestUtils,
	} ) => {
		const template = await requestUtils.createTemplate( 'wp_template', {
			slug: 'single-product',
			title: 'Custom Single Product',
			content: 'placeholder',
		} );

		await admin.visitSiteEditor( {
			postId: template.id,
			postType: 'wp_template',
			canvas: 'edit',
		} );

		await expect( editor.canvas.getByText( 'placeholder' ) ).toBeVisible();

		await editor.insertBlock( { name: blockData.slug } );

		await expect(
			await editor.getBlockByName( blockData.slug )
		).toBeVisible();
	} );

	test.describe( 'block registration', () => {
		test( 'should appear in the block inserter within the Single Product template', async ( {
			page,
			editor,
			requestUtils,
			admin,
		} ) => {
			const template = await requestUtils.createTemplate( 'wp_template', {
				slug: 'single-product',
				title: 'Custom Single Product',
				content: 'placeholder',
			} );

			await admin.visitSiteEditor( {
				postId: template.id,
				postType: 'wp_template',
				canvas: 'edit',
			} );
			await editor.openGlobalBlockInserter();
			await page.getByRole( 'tab', { name: 'Blocks' } ).click();
			const blockOption = page
				.getByRole( 'listbox', { name: 'WooCommerce' } )
				.getByRole( 'option', { name: blockData.name } );

			await expect( blockOption ).toBeVisible();
		} );

		test( 'should not appear in the block inserter within the Post Editor', async ( {
			admin,
			page,
			editor,
		} ) => {
			await admin.createNewPost();
			await editor.openGlobalBlockInserter();
			const blockOption = page
				.getByRole( 'listbox', { name: 'WooCommerce' } )
				.getByRole( 'option', { name: blockData.name } );

			await expect( blockOption ).toBeHidden();
		} );

		test( 'should not appear in the block inserter within the Page Editor', async ( {
			admin,
			page,
			editor,
		} ) => {
			await admin.createNewPost( {
				postType: 'page',
				title: 'New Page',
			} );
			await editor.openGlobalBlockInserter();
			const blockOption = page
				.getByRole( 'listbox', { name: 'WooCommerce' } )
				.getByRole( 'option', { name: blockData.name } );

			await expect( blockOption ).toBeHidden();
		} );
	} );
} );
