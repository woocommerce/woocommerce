/**
 * External dependencies
 */
import { test, expect, BLOCK_THEME_SLUG } from '@woocommerce/e2e-utils';

const templates = [
	{
		title: 'Cart',
		slug: 'cart',
		blockClassName: '.wc-block-cart',
	},
	{
		title: 'Checkout',
		slug: 'checkout',
		blockClassName: '.wc-block-checkout',
	},
];

for ( const template of templates ) {
	test.describe( 'Page Content Wrapper', () => {
		// Regression test for #48936: opening a template that uses this block
		// must not flag it as dirty (the block previously wrote postId/postType
		// to attributes on mount, enabling the Save button with no edits).
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
