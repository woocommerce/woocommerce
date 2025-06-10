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

	/**
	 * Configures the Single Product Block in the editor.
	 */
	async configureSingleProductBlock( name?: string ) {
		const singleProductBlock = await this.editor.getBlockByName(
			'woocommerce/single-product'
		);

		if ( name ) {
			await singleProductBlock
				.locator( 'input[type="search"]' )
				.fill( name );
			await singleProductBlock.getByText( 'Search' ).click();
			await singleProductBlock.getByText( name ).click();
		} else {
			await singleProductBlock
				.locator( 'input[type="radio"]' )
				.nth( 0 )
				.click();
		}

		await singleProductBlock.getByText( 'Done' ).click();
	}

	async createManagedStockProduct() {
		await wpCLI(
			'wc product create --name="Managed Stock" --regular_price=10 --manage_stock=true --stock_quantity=1 --user=admin'
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
		await editor.insertBlock( { name: 'woocommerce/store-notices' } );
		await editor.insertBlock( { name: 'woocommerce/single-product' } );

		const productName = 'Managed Stock';

		await blockUtils.configureSingleProductBlock( productName );

		await editor.publishAndVisitPost();

		// Add to cart once — succeeds.
		await page.click( 'text=Add to cart' );

		// Add to cart again — triggers out-of-stock error.
		await page.click( 'text=Add to cart' );

		// Verify error notice is displayed.
		await expect(
			page.locator( '.wc-block-components-notice-banner.is-error' )
		).toBeVisible();
	} );
} );
