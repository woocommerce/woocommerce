/**
 * External dependencies
 */
import { test, expect, FrontendUtils } from '@woocommerce/e2e-utils';

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

			// Prevent trying to insert the paragraph block before the editor is
			// ready.
			await expect(
				page.locator( template.blockClassName )
			).toBeVisible();

			await editor.insertBlock( {
				name: 'core/paragraph',
				attributes: { content: userText },
			} );

			await page
				.getByRole( 'button', { name: 'Save', exact: true } )
				.click();

			await page
				.getByRole( 'button', { name: 'Dismiss this notice' } )
				.filter( { hasText: 'updated' } )
				.waitFor();

			// Verify edits are in the template when viewed from the frontend.
			await template.visitPage( { frontendUtils } );
			await expect( page.getByText( userText ).first() ).toBeVisible();
		} );
	} );
}
