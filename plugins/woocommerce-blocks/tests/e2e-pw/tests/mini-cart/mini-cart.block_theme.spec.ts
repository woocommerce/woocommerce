/**
 * External dependencies
 */
import { BlockData } from '@woocommerce/e2e-types';
import { test, expect } from '@woocommerce/e2e-playwright-utils';

const blockData: BlockData = {
	name: 'woocommerce/mini-cart',
	mainClass: '.wc-block-mini-cart',
	selectors: {
		frontend: {
			drawer: '.wc-block-mini-cart__drawer',
		},
		editor: {},
	},
};

test.describe( `${ blockData.name } Block`, () => {
	test.describe( `standalone`, () => {
		test.beforeEach( async ( { admin, page, editor } ) => {
			await admin.createNewPost();
			await editor.insertBlock( { name: blockData.name } );
			await editor.publishPost();
			await page.waitForLoadState( 'networkidle' );
			const url = new URL( page.url() );
			const postId = url.searchParams.get( 'post' );
			await page.goto( `/?p=${ postId }`, { waitUntil: 'networkidle' } );
		} );

		test( 'should open the empty cart drawer', async ( { page } ) => {
			const miniCartButton = await page.getByLabel(
				'0 items in cart, total price of $0.00'
			);

			await miniCartButton.click();

			await expect(
				page
					.locator( blockData.selectors.frontend.drawer as string )
					.first()
			).toHaveText( 'Your cart is currently empty!' );
		} );
	} );

	test.describe( `with All products Block`, () => {
		test.beforeEach( async ( { admin, page, editor } ) => {
			await admin.createNewPost();
			await editor.insertBlock( { name: blockData.name } );
			await editor.insertBlock( { name: 'woocommerce/all-products' } );
			await editor.publishPost();
			await page.waitForLoadState( 'networkidle' );
			const url = new URL( page.url() );
			const postId = url.searchParams.get( 'post' );
			await page.goto( `/?p=${ postId }`, { waitUntil: 'networkidle' } );
		} );

		test( 'should open the filled cart drawer', async ( { page } ) => {
			const miniCartButton = await page.getByLabel(
				'0 items in cart, total price of $0.00'
			);

			await page.waitForLoadState( 'networkidle' );
			await page.click( 'text=Add to cart' );

			await miniCartButton.click();

			await expect(
				page.locator( '.wc-block-mini-cart__title' ).first()
			).toHaveText( 'Your cart (1 item)' );
		} );
	} );
} );
