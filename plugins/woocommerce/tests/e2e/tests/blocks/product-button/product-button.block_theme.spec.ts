/**
 * External dependencies
 */
import {
	expect,
	test,
	enableTestPlugin,
	guestFile,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { blockData } from './utils';

test.describe( `${ blockData.name } Block`, () => {
	// A guest cart, because all workers share the admin cart.
	test.use( { storageState: guestFile } );

	test.beforeEach( async ( { frontendUtils } ) => {
		await frontendUtils.goToShop();
	} );

	test( 'should be visible', async ( { frontendUtils } ) => {
		const blocks = await frontendUtils.getBlockByName( blockData.slug );
		await expect( blocks ).toHaveCount(
			blockData.selectors.frontend.productsToDisplay
		);
	} );

	test( 'should not enqueue add-to-cart-script', async ( { page } ) => {
		let isScriptEnqueued = false;
		page.on( 'request', ( request ) => {
			if ( request.url().includes( 'add-to-cart.min.js' ) )
				isScriptEnqueued = true;
		} );
		await page.reload();
		expect( isScriptEnqueued ).toBe( false );
	} );

	test( 'should add product to the cart', async ( {
		frontendUtils,
		page,
	} ) => {
		const blocks = await frontendUtils.getBlockByName( blockData.slug );
		const block = blocks.first();

		const productId = await block
			.locator( '[data-product_id]' )
			.getAttribute( 'data-product_id' );

		const productNameLocator = page.locator( `li.post-${ productId } h2` );
		await expect( productNameLocator ).not.toBeEmpty();

		const productName =
			( await productNameLocator.textContent() ) as string;

		await block.locator( 'loading' ).waitFor( {
			state: 'detached',
		} );
		await block.click();
		await expect( block.locator( 'loading' ) ).toBeHidden();
		await expect( block.getByRole( 'button' ) ).toHaveText( '1 in cart' );
		await expect( block.getByRole( 'button' ) ).toHaveAttribute(
			'aria-label',
			`1 in cart, Add to cart: “${ productName }”`
		);
		await expect( block.getByRole( 'link' ) ).toHaveText( 'View cart' );

		await frontendUtils.goToCheckout();
		const productElement = page.getByText( productName, {
			exact: true,
		} );
		await expect( productElement ).toBeVisible();
	} );

	test( 'the filter `woocommerce_product_add_to_cart_text` should be applied', async ( {
		context,
		frontendUtils,
	} ) => {
		await enableTestPlugin( context, 'custom-add-to-cart-button-text' );
		await frontendUtils.goToShop();
		const blocks = await frontendUtils.getBlockByName( blockData.slug );
		const buttonWithNewText = blocks.getByText( 'Buy Now' );
		await expect( buttonWithNewText ).toHaveCount(
			blockData.selectors.frontend.productsToDisplay
		);
	} );
} );
