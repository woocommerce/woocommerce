/**
 * External dependencies
 */
import { test, expect, BlockData } from '@woocommerce/e2e-utils';

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
	test( "can't be added in the Post Editor", async ( { admin, editor } ) => {
		await admin.createNewPost();

		await expect(
			editor.insertBlock( { name: blockData.slug } )
		).rejects.toThrow(
			new RegExp( `Block type '${ blockData.slug }' is not registered.` )
		);
	} );

	test( "can't be added in the Post Editor - Product Catalog Template", async ( {
		admin,
		editor,
		editorUtils,
	} ) => {
		await admin.visitSiteEditor( {
			postId: `woocommerce/woocommerce//archive-product`,
			postType: 'wp_template',
		} );
		await editorUtils.enterEditMode();

		await editor.setContent( '' );

		try {
			await editor.insertBlock( { name: blockData.slug } );
		} catch ( _error ) {
			// noop
		}

		await expect(
			await editorUtils.getBlockByName( blockData.slug )
		).toBeHidden();
	} );

	test( 'can be added in the Post Editor - Single Product Template', async ( {
		admin,
		editor,
		editorUtils,
	} ) => {
		await admin.visitSiteEditor( {
			postId: `woocommerce/woocommerce//single-product`,
			postType: 'wp_template',
		} );
		await editorUtils.enterEditMode();
		await editor.setContent( '' );
		await editor.insertBlock( { name: blockData.slug } );

		await expect(
			await editorUtils.getBlockByName( blockData.slug )
		).toBeVisible();
	} );
} );
