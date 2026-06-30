/**
 * External dependencies
 */
import {
	test,
	expect,
	FrontendUtils,
	BLOCK_THEME_SLUG,
} from '@woocommerce/e2e-utils';

// Instead of testing the block individually, we test the Cart and Checkout
// templates, which make use of the block.
const templates = [
	{
		title: 'Cart',
		slug: 'cart',
		blockClassName: '.wc-block-cart',
		visitPage: async ( {
			frontendUtils,
		}: {
			frontendUtils: FrontendUtils;
		} ) => {
			await frontendUtils.goToCart();
		},
	},
	{
		title: 'Checkout',
		slug: 'checkout',
		blockClassName: '.wc-block-checkout',
		visitPage: async ( {
			frontendUtils,
		}: {
			frontendUtils: FrontendUtils;
		} ) => {
			await frontendUtils.goToShop();
			await frontendUtils.addToCart();
			await frontendUtils.goToCheckout();
		},
	},
];
const userText = 'Hello World in the page';

for ( const template of templates ) {
	test.describe( 'Page Content Wrapper', () => {
		test( `the content of the ${ template.title } page is correctly rendered in the ${ template.title } template`, async ( {
			page,
			admin,
			editor,
			frontendUtils,
			requestUtils,
		} ) => {
			const pageData = await requestUtils.rest( {
				path: 'wp/v2/pages?slug=' + template.slug,
			} );
			const pageId = pageData[ 0 ].id;

			await admin.editPost( pageId );

			await expect(
				editor.canvas.locator( template.blockClassName )
			).toBeVisible();

			// editor.isertBlock() doesn't work here.
			await editor.insertBlockUsingGlobalInserter( 'Paragraph' );
			await editor.canvas
				.getByRole( 'document', { name: 'Empty block' } )
				.fill( userText );

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			// Verify edits are in the template when viewed from the frontend.
			await template.visitPage( { frontendUtils } );
			await expect( page.getByText( userText ).first() ).toBeVisible();
		} );

		// Regression test for #48936: opening a template that uses this block in
		// the Site Editor must not flag it as dirty. Previously the block wrote
		// postId/postType to its attributes on mount, enabling the Save button
		// with no user edits — which could overwrite the template with empty
		// content.
		test( `opening the ${ template.title } template in the Site Editor does not enable the Save button`, async ( {
			page,
			admin,
			editor,
		} ) => {
			await admin.visitSiteEditor( {
				postId: `${ BLOCK_THEME_SLUG }//page-${ template.slug }`,
				postType: 'wp_template',
				canvas: 'edit',
			} );

			// Wait for the template content to render before asserting state.
			await expect(
				editor.canvas.locator( template.blockClassName )
			).toBeVisible();

			await expect(
				page.getByRole( 'button', { name: 'Save', exact: true } )
			).toBeDisabled();
		} );
	} );
}
