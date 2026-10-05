/**
 * External dependencies
 */
import { test as base, expect, wpCLI } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import AddToCartWithOptionsPage from '../add-to-cart-with-options/add-to-cart-with-options.page';
import { CART_LINE_IDENTITY_PLUGIN, seedDeclaredChildLine } from './utils';

const test = base.extend< {
	addToCartWithOptionsPage: AddToCartWithOptionsPage;
} >( {
	addToCartWithOptionsPage: async (
		{ page, admin, editor },
		provideFixture
	) => {
		await provideFixture(
			new AddToCartWithOptionsPage( { page, admin, editor } )
		);
	},
} );

test.describe( 'ProductButton in-cart count', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activatePlugin( CART_LINE_IDENTITY_PLUGIN );
	} );

	test( 'does not count a child cart line in the add to cart button', async ( {
		page,
		frontendUtils,
		addToCartWithOptionsPage,
	} ) => {
		await addToCartWithOptionsPage.createPostWithProductBlock(
			'hoodie',
			'hoodie-blue-yes'
		);
		const postUrl = page.url();

		await frontendUtils.emptyCart();

		const variationResult = await wpCLI(
			'post list --post_type=product_variation --field=ID --name="Hoodie - Blue, Yes" --format=ids'
		);
		const variationId = Number(
			variationResult.stdout.match( /\d+/g )?.pop()
		);
		expect( Number.isInteger( variationId ) ).toBe( true );

		await seedDeclaredChildLine( page, variationId, 'parent-key' );
		const cartItems = await page.evaluate( async () => {
			const response = await fetch( '/wp-json/wc/store/v1/cart' );
			return ( await response.json() ).items;
		} );
		expect( cartItems ).toHaveLength( 1 );
		expect( cartItems[ 0 ].parent_item_key ).toBe( 'parent-key' );

		// Reads the server-rendered HTML without running scripts.
		const readServerRenderedButtonText = async () => {
			const response = await page.request.get( postUrl );
			const button = ( await response.text() ).match(
				/<button\b[^>]*class="[^"]*\bsingle_add_to_cart_button\b[^"]*"[^>]*>([\s\S]*?)<\/button>/
			);
			expect( button ).not.toBeNull();

			return button?.[ 1 ]?.replace( /<[^>]*>/g, '' ).trim();
		};

		const addToCartButton = page
			.locator( '.wp-block-add-to-cart-with-options' )
			.locator( '.single_add_to_cart_button' );

		expect( await readServerRenderedButtonText() ).toBe( 'Add to cart' );
		await page.goto( postUrl );
		// The server renders the button hidden and the client store reveals it, so
		// waiting for it to be visible makes the next text check read the hydrated count.
		await expect( addToCartButton ).toBeVisible();
		await expect( addToCartButton ).toHaveText( 'Add to cart' );

		await addToCartButton.click();
		await expect( addToCartButton ).toHaveText( '1 in cart' );

		expect( await readServerRenderedButtonText() ).toBe( '1 in cart' );
		await page.reload();
		await expect( addToCartButton ).toBeVisible();
		await expect( addToCartButton ).toHaveText( '1 in cart' );
	} );
} );
