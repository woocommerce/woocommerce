/**
 * External dependencies
 */
import { ElementHandle, Page } from 'puppeteer';
/**
 * Internal dependencies
 */
import { BaseElement } from './BaseElement';

export class OrdersActivityPanel extends BaseElement {
	constructor( page: Page ) {
		super( page, '.woocommerce-order-activity-card' );
	}

	async getDisplayedOrders(): Promise< string[] > {
		await this.page.waitForSelector(
			'.woocommerce-order-activity-card h3'
		);
		const list = await this.page.$$(
			'.woocommerce-order-activity-card h3'
		);
		return Promise.all(
			list.map( async ( item: ElementHandle ) => {
				const textContent = await page.evaluate(
					( el ) => el.textContent,
					item
				);
				return textContent.trim();
			} )
		);
	}
}
