/**
 * External dependencies
 */
import { test, expect, BLOCK_THEME_SLUG } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import {
	getProductsNameFromClassicTemplate,
	getProductsNameFromProductQuery,
	insertProductsQuery,
} from './utils';

const routes = [
	{
		testTitle:
			'Products block matches the classic template block on the shop page',
		templateTitle: 'Product Catalog',
		slug: 'archive-product',
		frontendPage: '/shop/',
	},
	{
		testTitle:
			'Products block matches the classic template block on product search results',
		templateTitle: 'Product Search Results',
		slug: 'product-search-results',
		frontendPage: '/?s=shirt&post_type=product',
	},
];

for ( const { testTitle, templateTitle, slug, frontendPage } of routes ) {
	test.describe( `${ templateTitle } template, Products block`, () => {
		test( testTitle, async ( { admin, editor, page } ) => {
			await admin.visitSiteEditor( {
				postId: `${ BLOCK_THEME_SLUG }//${ slug }`,
				postType: 'wp_template',
				canvas: 'edit',
			} );
			await editor.setContent( '' );
			await insertProductsQuery( editor );
			await editor.insertBlock( { name: 'woocommerce/legacy-template' } );
			await editor.canvas.locator( 'body' ).click();

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			await page.goto( frontendPage );
			await expect(
				page.locator( '.woocommerce-loop-product__title' ).first()
			).toBeVisible();

			const classicProducts =
				await getProductsNameFromClassicTemplate( page );
			const productQueryProducts =
				await getProductsNameFromProductQuery( page );

			expect( classicProducts.length ).toBeGreaterThan( 1 );
			expect( classicProducts ).toEqual( productQueryProducts );
		} );
	} );
}
