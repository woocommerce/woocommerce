/**
 * External dependencies
 */
import {
	test,
	expect,
	BlockData,
	BLOCK_THEME_SLUG,
} from '@woocommerce/e2e-utils';

// Block is soft-deprecated, meaning that it's hidden from the inserter.
const blockData: BlockData = {
	name: 'Related Products',
	slug: 'woocommerce/related-products',
	mainClass: '.wc-block-related-products',
	selectors: {
		frontend: {},
		editor: {},
	},
};

test.describe( `${ blockData.name } Block`, () => {
	test( 'keeps the deprecated block unavailable across editor contexts', async ( {
		admin,
		editor,
	} ) => {
		await test.step( 'Post Editor', async () => {
			await admin.createNewPost();

			try {
				await editor.insertBlock( { name: blockData.slug } );
			} catch {
				// noop
			}

			await expect(
				await editor.getBlockByName( blockData.slug )
			).toBeHidden();
		} );

		await test.step( 'Product Catalog template', async () => {
			await admin.visitSiteEditor( {
				postId: `${ BLOCK_THEME_SLUG }//archive-product`,
				postType: 'wp_template',
				canvas: 'edit',
			} );

			await editor.setContent( '' );

			try {
				await editor.insertBlock( { name: blockData.slug } );
			} catch {
				// noop
			}

			await expect(
				await editor.getBlockByName( blockData.slug )
			).toBeHidden();
		} );

		await test.step( 'Single Product template', async () => {
			await admin.visitSiteEditor( {
				postId: `${ BLOCK_THEME_SLUG }//single-product`,
				postType: 'wp_template',
				canvas: 'edit',
			} );
			await editor.setContent( '' );

			// Inserting Related Products by name
			// (but it's a Product Collection variation).
			await editor.insertBlockUsingGlobalInserter( blockData.name );

			await expect(
				editor.canvas
					.getByLabel( 'Block: Related Products', { exact: true } )
					.first()
			).toBeVisible();

			// Verifying by slug - it's expected it's NOT woocommerce/related-products.
			await expect(
				await editor.getBlockByName( blockData.slug )
			).toBeHidden();
		} );
	} );
} );
