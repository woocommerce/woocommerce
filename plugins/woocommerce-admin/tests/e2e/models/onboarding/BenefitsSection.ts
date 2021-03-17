import { Page } from 'puppeteer';
import { getElementByText, waitForElementByText } from '../../utils/actions';

export class BenefitsSection {
	page: Page;

	constructor( page: Page ) {
		this.page = page;
	}

	async isDisplayed() {
		await waitForElementByText(
			'h2',
			'Enhance your store with Jetpack and WooCommerce Shipping & Tax'
		);
	}

	async noThanks() {
		// Click on "No thanks" button to move to the next step
		const button = await getElementByText( 'button', 'No thanks' );
		await button?.click();
	}
}
