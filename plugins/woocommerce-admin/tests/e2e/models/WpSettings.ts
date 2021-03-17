import { WP_ADMIN_PERMALINK_SETTINGS } from '../utils/constants';
import { Page } from 'puppeteer';
import { getElementByText, waitForElementByText } from '../utils/actions';

export class WpSettings {
	page: Page;
	constructor( page: Page ) {
		this.page = page;
	}

	async openPermalinkSettings() {
		await this.page.goto( WP_ADMIN_PERMALINK_SETTINGS, {
			waitUntil: 'networkidle0',
		} );
		await waitForElementByText( 'h1', 'Permalink Settings' );
	}

	async saveSettings() {
		await page.click( '#submit' );
		await this.page.waitForNavigation( {
			waitUntil: 'networkidle0',
		} );
	}
}
