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
		return this.page.locator( '.wc-block-cart-items__row', {
			hasText: new RegExp( productName ),
		} );
	}
}
