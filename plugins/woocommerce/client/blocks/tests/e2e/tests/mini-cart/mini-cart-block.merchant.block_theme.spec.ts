/**
 * External dependencies
 */
import { test, expect, BlockData } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { blockData as baseBlockData } from './utils';

const blockData: BlockData = {
	...baseBlockData,
	selectors: {
		...baseBlockData.selectors,
		editor: {
			block: '.wp-block-woocommerce-mini-cart',
			insertButton: "//button//span[text()='Mini-Cart']",
		},
	},
};

test.describe( 'Merchant → Mini Cart', () => {
	test.describe( 'in FSE editor', () => {
		test( 'can be inserted in FSE area', async ( { editor, admin } ) => {
			await admin.visitSiteEditor( {
				postId: `woocommerce/woocommerce//single-product`,
				postType: 'wp_template',
				canvas: 'edit',
			} );

			await editor.setContent( '' );

			await editor.insertBlock( { name: blockData.slug } );
			await expect(
				editor.canvas.getByLabel( 'Block: Mini-Cart' )
			).toBeVisible();
		} );

		test( 'can only be inserted once', async ( { editor, admin } ) => {
			await admin.visitSiteEditor( {
				postId: `woocommerce/woocommerce//single-product`,
				postType: 'wp_template',
				canvas: 'edit',
			} );
			await editor.openGlobalBlockInserter();

			await editor.page
				.getByRole( 'searchbox', { name: 'Search' } )
				.fill( blockData.slug );

			const miniCartButton = editor.page.getByRole( 'option', {
				name: blockData.name,
			} );

			await expect( miniCartButton ).toBeVisible();
			await expect( miniCartButton ).toBeDisabled();
		} );
	} );
} );
