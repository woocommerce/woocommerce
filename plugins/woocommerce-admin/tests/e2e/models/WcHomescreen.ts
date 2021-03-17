import { Page } from 'puppeteer';
import { getElementByText, waitForElementByText } from '../utils/actions';

export class WcHomescreen {
	page: Page;

	constructor( page: Page ) {
		this.page = page;
	}

	async isDisplayed() {
		// Wait for Benefits section to appear
		await waitForElementByText( 'h1', 'Home' );
	}

	async possiblyDismissWelcomeModal() {
		// Wait for Benefits section to appear
		const modal = await getElementByText(
			'h2',
			'Welcome to your WooCommerce store’s online HQ!'
		);

		if ( modal ) {
			let button = await getElementByText( 'button', 'Next' );
			await button?.click();
			button = await getElementByText( 'button', 'Next' );
			await button?.click();
			await this.page.click( '.components-guide__finish-button' );
		}
	}

	async getTaskList() {
		// Log out link in admin bar is not visible so can't be clicked directly.
		await page.waitForSelector(
			'.woocommerce-task-card .woocommerce-list__item-title'
		);
		await waitForElementByText( 'p', 'Get ready to start selling' );
		const list = await this.page.$$eval(
			'.woocommerce-task-card .woocommerce-list__item-title',
			( items ) => items.map( ( item ) => item.textContent )
		);
		return list.map( ( item: string | null ) => {
			const match = item?.match( /(.+)[0-9] minute/ );
			if ( match && match.length > 1 ) {
				return match[ 1 ];
			}
			return item;
		} );
	}

	async clickOnTaskList( taskTitle: string ) {
		const item = await getElementByText( 'div', taskTitle );
		await item?.click();
		await waitForElementByText( 'h1', taskTitle );
	}
}
