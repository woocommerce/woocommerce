/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { blockData, handleAddToCartAjaxSetting } from './utils';

test.describe( `${ blockData.name } Block with AJAX add to cart disabled`, () => {
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
	} );
} );
