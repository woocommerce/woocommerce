/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import { test as base, expect, Editor, wpCLI } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */

class BlockUtils {
	editor: Editor;
	page: Page;

	constructor( { editor, page }: { editor: Editor; page: Page } ) {
		this.editor = editor;
		this.page = page;
	}

	async createManagedStockProduct() {
		await wpCLI(
			'wc product create --name="A Managed Stock" --regular_price=10 --manage_stock=true --stock_quantity=1 --user=admin'
		);
	}
}

const test = base.extend< { blockUtils: BlockUtils } >( {
	blockUtils: async ( { editor, page }, use ) => {
		await use( new BlockUtils( { editor, page } ) );
	},
} );

test.describe( 'Product Page: error notices when adding out-of-stock products', () => {
	test( 'displays error notice when attempting to add product beyond stock limit', async ( {
		admin,
		editor,
		blockUtils,
		page,
	} ) => {
		await blockUtils.createManagedStockProduct();
		await admin.createNewPost();
		await editor.insertBlock( { name: 'woocommerce/product-collection' } );

		const singleProduct = await editor.getBlockByName(
			'woocommerce/product-collection'
		);

		await expect(
			singleProduct.locator(
				'.wc-blocks-product-collection__collections-create button'
			)
		).toBeVisible();

		await singleProduct
			.locator(
				'.wc-blocks-product-collection__collections-create button'
			)
			.click();

		const productName = 'A Managed Stock';

		await editor.publishAndVisitPost();

		const productCart = page
			.locator( '.wc-block-product' )
			.filter( { hasText: productName } );

		await expect( productCart ).toBeVisible();

		// Add to cart once — succeeds.
		await productCart.locator( '.add_to_cart_button' ).click();

		// Add to cart again — triggers out-of-stock error.
		await productCart.locator( '.add_to_cart_button' ).click();

		// Verify error notice is displayed.
		await expect(
			page.locator( '.wc-block-components-notice-banner.is-error' )
		).toBeVisible();
	} );
} );
