import { Page } from 'puppeteer';
import { getElementByText, waitForElementByText } from '../../utils/actions';

export class ThemeSection {
	page: Page;

	constructor( page: Page ) {
		this.page = page;
	}

	async isDisplayed() {
		await waitForElementByText( 'h2', 'Choose a theme' );
		await waitForElementByText( 'button', 'All themes' );
	}

	async continueWithActiveTheme() {
		const button = await getElementByText(
			'button',
			'Continue with my active theme'
		);
		await button?.click();
	}
}
