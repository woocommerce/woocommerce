/**
 * External dependencies
 */
import {
	expect,
	test as base,
	CLASSIC_THEME_SLUG,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { blockData } from './utils';
import ProductCollectionPage from '../product-collection/product-collection.page';

const test = base.extend< { productCollectionPage: ProductCollectionPage } >( {
	productCollectionPage: async ( { page, admin, editor }, use ) => {
		const pageObject = new ProductCollectionPage( {
			page,
			admin,
			editor,
		} );
		await use( pageObject );
	},
} );
test.describe( `${ blockData.name } Block`, () => {
	test.beforeEach( async ( { page, requestUtils } ) => {
		await requestUtils.activateTheme( CLASSIC_THEME_SLUG );
		await page.goto( '/product-collection/' );
	} );

	test( 'should be visible', async ( { frontendUtils } ) => {
		const blocks = await frontendUtils.getBlockByName( blockData.slug );
		await expect( blocks ).toHaveCount(
			blockData.selectors.frontend.productsToDisplay
		);
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

		const productNameLocator = page.locator( `li.post-${ productId } h3` );
		await expect( productNameLocator ).not.toBeEmpty();

		const productName =
			( await productNameLocator.textContent() ) as string;

		await block.locator( 'loading' ).waitFor( {
			state: 'detached',
		} );
		await block.click();
		await expect( block.getByRole( 'button' ) ).toHaveText( '1 in cart' );
		await expect( block.getByRole( 'link' ) ).toHaveText( 'View cart' );

		await frontendUtils.goToCheckout();
		const productElement = page.getByText( productName, {
			exact: true,
		} );
		await expect( productElement ).toBeVisible();
	} );

	test( 'should compound quantity when rapidly clicking same button', async ( {
		frontendUtils,
		page,
	} ) => {
		const blocks = await frontendUtils.getBlockByName( blockData.slug );
		const block = blocks.first();

		await block.locator( 'loading' ).waitFor( {
			state: 'detached',
		} );

		// Set up waitForResponse BEFORE the clicks to avoid a race condition:
		// on fast networks the batched Store API request may complete before we
		// start listening. Asserting before the request settles is what makes
		// this test flaky. Mirrors the add-to-cart-with-options rapid-click test.
		const batchPromise = page.waitForResponse( '**/wc/store/v1/batch**' );

		// Click the same button 3 times rapidly.
		await block.click();
		await block.click();
		await block.click();

		// Wait for the batched add-to-cart request to complete before asserting.
		await batchPromise;

		// All 3 clicks should compound to "3 in cart".
		await expect( block.getByRole( 'button' ) ).toHaveText( '3 in cart', {
			timeout: 15000,
		} );
	} );
} );
