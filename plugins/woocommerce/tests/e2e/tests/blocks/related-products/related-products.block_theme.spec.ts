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
	test( 'inserts the supported Related Products variation', async ( {
		admin,
		editor,
	} ) => {
		await admin.visitSiteEditor( {
			postId: `${ BLOCK_THEME_SLUG }//single-product`,
			postType: 'wp_template',
			canvas: 'edit',
		} );
		await editor.setContent( '' );

		await editor.insertBlockUsingGlobalInserter( blockData.name );

		await expect(
			await editor.getBlockByName( 'woocommerce/product-collection' )
		).toBeVisible();
		await expect(
			await editor.getBlockByName( blockData.slug )
		).toBeHidden();
	} );
} );
