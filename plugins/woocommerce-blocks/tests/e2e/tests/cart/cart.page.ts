/**
 * External dependencies
 */
import { Page } from '@playwright/test';

export class CartPage {
	public page: Page;

	constructor( { page }: { page: Page } ) {
		this.page = page;
	}

	async findProductRow( productName: string ) {
		await this.page.waitForSelector( '.wc-block-cart-items__row' );
		const productRows = this.page.locator( '.wc-block-cart-items__row' );
		const rowCount = await productRows.count();

		for ( let i = 0; i < rowCount; i++ ) {
			const productRow = productRows.nth( i );
			const nameElement = productRow.locator(
				'.wc-block-components-product-name'
			);
			const nameText = await nameElement.innerText();
			if ( nameText === productName ) {
				return productRow;
			}
		}

		return productRows;
	}
}
