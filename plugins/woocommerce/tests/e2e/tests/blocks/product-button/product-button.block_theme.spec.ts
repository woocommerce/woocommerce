/**
 * External dependencies
 */
import type { Request } from '@playwright/test';
import { expect, test } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { blockData, handleAddToCartAjaxSetting } from './utils';

test.describe( `${ blockData.name } Block`, () => {
	test( 'should support the default AJAX add-to-cart flow', async ( {
		frontendUtils,
		page,
	} ) => {
		await test.step( 'should be visible', async () => {
			await frontendUtils.goToShop();
			const blocks = await frontendUtils.getBlockByName( blockData.slug );
			await expect( blocks ).toHaveCount(
				blockData.selectors.frontend.productsToDisplay
			);
		} );

		await test.step( 'should not enqueue add-to-cart-script', async () => {
			let isScriptEnqueued = false;
			const requestListener = ( request: Request ) => {
				if ( request.url().includes( 'add-to-cart.min.js' ) ) {
					isScriptEnqueued = true;
				}
			};

			page.on( 'request', requestListener );
			try {
				await page.reload();
				expect( isScriptEnqueued ).toBe( false );
			} finally {
				page.off( 'request', requestListener );
			}
		} );

		await test.step( 'should add product to the cart', async () => {
			const blocks = await frontendUtils.getBlockByName( blockData.slug );
			const block = blocks.first();

			const productId = await block
				.locator( '[data-product_id]' )
				.getAttribute( 'data-product_id' );

			const productNameLocator = page.locator(
				`li.post-${ productId } h2`
			);
			await expect( productNameLocator ).not.toBeEmpty();

			const productName =
				( await productNameLocator.textContent() ) as string;

			await block.locator( 'loading' ).waitFor( {
				state: 'detached',
			} );
			await block.click();
			await expect( block.locator( 'loading' ) ).toBeHidden();
			await expect( block.getByRole( 'button' ) ).toHaveText(
				'1 in cart'
			);
			await expect( block.getByRole( 'link' ) ).toHaveText( 'View cart' );

			await frontendUtils.goToCheckout();
			const productElement = page.getByText( productName, {
				exact: true,
			} );
			await expect( productElement ).toBeVisible();
		} );
	} );

	test( 'should add product to the cart - with ajax disabled', async ( {
		frontendUtils,
		page,
		admin,
	} ) => {
		await handleAddToCartAjaxSetting( admin, page, {
			isChecked: true,
		} );
		await frontendUtils.goToShop();

		const blocks = await frontendUtils.getBlockByName( blockData.slug );
		const block = blocks.first();
		const button = block.getByRole( 'link' );

		const productId = await button.getAttribute( 'data-product_id' );

		const productNameLocator = page.locator( `li.post-${ productId } h2` );
		await expect( productNameLocator ).not.toBeEmpty();

		const productName =
			( await productNameLocator.textContent() ) as string;

		await block.click();

		await expect(
			page.locator( `a[href*="cart=${ productId }"]` )
		).toBeVisible();

		await frontendUtils.goToCheckout();

		const productElement = page.getByText( productName, {
			exact: true,
		} );

		await expect( productElement ).toBeVisible();

		await handleAddToCartAjaxSetting( admin, page, {
			isChecked: false,
		} );
	} );

	test( 'the filter `woocommerce_product_add_to_cart_text` should be applied', async ( {
		requestUtils,
		frontendUtils,
	} ) => {
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-custom-add-to-cart-button-text'
		);
		await frontendUtils.goToShop();
		const blocks = await frontendUtils.getBlockByName( blockData.slug );
		const buttonWithNewText = blocks.getByText( 'Buy Now' );
		await expect( buttonWithNewText ).toHaveCount(
			blockData.selectors.frontend.productsToDisplay
		);
	} );
} );
