/**
 * External dependencies
 */
import {
	test,
	expect,
	BlockData,
	BLOCK_THEME_SLUG,
} from '@woocommerce/e2e-utils';

const blockData: BlockData = {
	name: 'Mini-Cart',
	slug: 'woocommerce/mini-cart',
	mainClass: '.wc-block-minicart',
	selectors: {
		editor: {
			block: '.wp-block-woocommerce-mini-cart',
			insertButton: "//button//span[text()='Mini-Cart']",
		},
		frontend: {},
	},
};

test.describe( 'Merchant → Mini Cart', () => {
	test.describe( 'in FSE editor', () => {
		test( 'can be inserted in FSE area', async ( { editor, admin } ) => {
			await admin.visitSiteEditor( {
				postId: `${ BLOCK_THEME_SLUG }//single-product`,
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
				postId: `${ BLOCK_THEME_SLUG }//single-product`,
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

		test( 'renders filled and empty views in the Mini-Cart template part', async ( {
			editor,
			admin,
		} ) => {
			await admin.visitSiteEditor( {
				postType: 'wp_template_part',
			} );
			await editor.openTemplate( { templateName: 'Mini-Cart' } );

			const filledMiniCart = await editor.getBlockByName(
				'woocommerce/filled-mini-cart-contents-block'
			);
			const emptyMiniCart = await editor.getBlockByName(
				'woocommerce/empty-mini-cart-contents-block'
			);

			await expect( filledMiniCart ).toBeVisible();
			await expect( emptyMiniCart ).toBeAttached();
		} );
	} );
} );
