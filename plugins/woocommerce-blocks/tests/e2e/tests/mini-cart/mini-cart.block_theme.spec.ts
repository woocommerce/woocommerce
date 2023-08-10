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
			drawerCloseButton: 'button[aria-label="Close"]',
		},
		editor: {},
	},
};

const getMiniCartButton = async ( { page } ) => {
	return page.getByLabel( '0 items in cart, total price of $0.00' );
};

test.describe( `${ blockData.name } Block`, () => {
	test.describe( `standalone`, () => {
		test.beforeEach( async ( { admin, page, editor } ) => {
			await admin.createNewPost( { legacyCanvas: true } );
			await editor.insertBlock( { name: blockData.name } );
			await editor.publishPost();
			const url = new URL( page.url() );
			const postId = url.searchParams.get( 'post' );
			await page.goto( `/?p=${ postId }`, { waitUntil: 'commit' } );
		} );

		test( 'should open the empty cart drawer', async ( { page } ) => {
			const miniCartButton = await getMiniCartButton( { page } );

			await miniCartButton.click();

			await expect(
				page.locator( blockData.selectors.frontend.drawer ).first()
			).toHaveText( 'Your cart is currently empty!' );
		} );

		test( 'should close the drawer when clicking on the close button', async ( {
			page,
		} ) => {
			const miniCartButton = await getMiniCartButton( { page } );

			await miniCartButton.click();

			await expect(
				page.locator( blockData.selectors.frontend.drawer ).first()
			).toHaveText( 'Your cart is currently empty!' );

			// Wait for the drawer to fully open.
			await page.waitForSelector(
				blockData.selectors.frontend.drawerCloseButton
			);

			const closeButton = page.locator(
				blockData.selectors.frontend.drawerCloseButton
			);

			await closeButton?.click();

			// Wait for the drawer to fully close.
			await page.waitForSelector( blockData.selectors.frontend.drawer, {
				state: 'detached',
			} );

			expect(
				await page
					.locator( blockData.selectors.frontend.drawer )
					.count()
			).toEqual( 0 );
		} );

		test( 'should close the drawer when clicking outside the drawer', async ( {
			page,
		} ) => {
			const miniCartButton = await getMiniCartButton( { page } );

			await miniCartButton.click();

			await expect(
				page.locator( blockData.selectors.frontend.drawer ).first()
			).toHaveText( 'Your cart is currently empty!' );

			// Wait for the drawer to fully open.
			await page.waitForSelector(
				blockData.selectors.frontend.drawerCloseButton
			);

			await page.mouse.click( 50, 200 );

			// Wait for the drawer to fully close.
			await page.waitForSelector( blockData.selectors.frontend.drawer, {
				state: 'detached',
			} );

			expect(
				await page
					.locator( blockData.selectors.frontend.drawer )
					.count()
			).toEqual( 0 );
		} );
	} );

	test.describe( `with All products Block`, () => {
		test.beforeEach( async ( { admin, page, editor } ) => {
			await admin.createNewPost( { legacyCanvas: true } );
			await editor.insertBlock( { name: blockData.name } );
			await editor.insertBlock( { name: 'woocommerce/all-products' } );
			await editor.publishPost();
			const url = new URL( page.url() );
			const postId = url.searchParams.get( 'post' );
			await page.goto( `/?p=${ postId }`, { waitUntil: 'commit' } );
		} );

		test( 'should open the filled cart drawer', async ( { page } ) => {
			const miniCartButton = await getMiniCartButton( { page } );

			await page.click( 'text=Add to cart' );

			await miniCartButton.click();

			await expect(
				page.locator( '.wc-block-mini-cart__title' ).first()
			).toHaveText( 'Your cart (1 item)' );
		} );
	} );
} );
