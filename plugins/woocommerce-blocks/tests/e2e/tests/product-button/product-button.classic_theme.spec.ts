/**
 * External dependencies
 */
import { CLASSIC_THEME_SLUG } from '@woocommerce/e2e-utils';
import { expect, test as base } from '@woocommerce/e2e-playwright-utils';

/**
 * Internal dependencies
 */
import { blockData } from './utils';
import ProductCollectionPage from '../product-collection/product-collection.page';

const test = base.extend< { productCollectionPage: ProductCollectionPage } >( {
	productCollectionPage: async (
		{ page, admin, editor, templateApiUtils, editorUtils },
		use
	) => {
		const pageObject = new ProductCollectionPage( {
			page,
			admin,
			editor,
			templateApiUtils,
			editorUtils,
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

		const productName = await page
			.locator( `li.post-${ productId } h3` )
			.textContent();

		// We want to fail the test if the product name is not found.
		// eslint-disable-next-line playwright/no-conditional-in-test
		if ( ! productName ) {
			return test.fail( ! productName, 'Product name was not found' );
		}

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
} );
