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

test.describe( 'ProductButton cart count excludes declared child lines', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activatePlugin( CART_LINE_IDENTITY_PLUGIN );
	} );

	test( 'a direct variation add starts keyless and server and client counts agree', async ( {
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

		await seedDeclaredChildLine(
			page,
			variationId,
			'parent-key-that-is-not-in-the-cart'
		);

		const readServerRenderedButtonText = async () => {
			const response = await page.request.get( postUrl );
			expect( response.ok() ).toBe( true );

			const html = await response.text();
			const button = html.match(
				/<button\b[^>]*class="[^"]*\bsingle_add_to_cart_button\b[^"]*"[^>]*>([\s\S]*?)<\/button>/
			);
			expect( button ).not.toBeNull();

			return button?.[ 1 ]?.replace( /<[^>]*>/g, '' ).trim();
		};

		// This separate request reads the server HTML without executing scripts.
		expect( await readServerRenderedButtonText() ).toBe( 'Add to cart' );

		await page.goto( postUrl );
		const addToCartButton = page
			.locator( '.wp-block-add-to-cart-with-options' )
			.locator( '.single_add_to_cart_button' );
		await expect( addToCartButton ).toHaveText( 'Add to cart' );

		const batchResponse = page.waitForResponse(
			( response ) =>
				response.url().includes( '/wc/store/v1/batch' ) &&
				response.request().method() === 'POST'
		);
		await addToCartButton.click();

		const batchRequest = ( await batchResponse ).request().postDataJSON();
		expect( batchRequest.requests ).toHaveLength( 1 );
		expect( batchRequest.requests[ 0 ].path ).toBe(
			'/wc/store/v1/cart/add-item'
		);
		expect( batchRequest.requests[ 0 ].body ).toMatchObject( {
			id: variationId,
		} );
		expect( batchRequest.requests[ 0 ].body ).not.toHaveProperty( 'key' );

		await expect( addToCartButton ).toHaveText( '1 in cart' );
		await expect(
			page.locator( '.wc-block-components-notice-banner.is-error' )
		).toHaveCount( 0 );

		expect( await readServerRenderedButtonText() ).toBe( '1 in cart' );
		await page.reload();
		await expect( addToCartButton ).toHaveText( '1 in cart' );
	} );
} );
