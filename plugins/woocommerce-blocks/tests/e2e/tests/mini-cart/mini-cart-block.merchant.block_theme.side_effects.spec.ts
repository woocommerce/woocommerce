/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import { BlockData } from '@woocommerce/e2e-types';
import { WC_TEMPLATES_SLUG } from '@woocommerce/e2e-utils';

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
		test( 'can be inserted in FSE area', async ( {
			editorUtils,
			editor,
			admin,
		} ) => {
			await admin.visitSiteEditor( {
				postId: `${ WC_TEMPLATES_SLUG }//single-product`,
				postType: 'wp_template',
			} );
			await editorUtils.enterEditMode();

			await editor.setContent( '' );

			await editor.insertBlock( { name: blockData.slug } );
			await expect(
				await editorUtils.getBlockByName( blockData.slug )
			).toBeVisible();
			await editor.saveSiteEditorEntities();
		} );

		test( 'can only be inserted once', async ( { editorUtils, admin } ) => {
			await admin.visitSiteEditor( {
				postId: `${ WC_TEMPLATES_SLUG }//single-product`,
				postType: 'wp_template',
			} );
			await editorUtils.enterEditMode();
			await editorUtils.openGlobalBlockInserter();

			await editorUtils.page
				.getByLabel( 'Search for blocks and patterns' )
				.fill( blockData.slug );

			// Await for blocks commercial to be loaded in the Blocks inserter.
			await expect(
				editorUtils.page.locator(
					'.block-directory-downloadable-block-list-item__details'
				)
			).toBeVisible();

			const miniCartButton = editorUtils.page.getByRole( 'option', {
				name: blockData.name,
			} );

			await expect( miniCartButton ).toBeVisible();
			await expect( miniCartButton ).toBeDisabled();
		} );
	} );
} );
