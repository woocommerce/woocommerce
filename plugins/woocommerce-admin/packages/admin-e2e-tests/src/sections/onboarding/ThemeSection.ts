/**
 * Internal dependencies
 */
import { BasePage } from '../../pages/BasePage';
import { waitForElementByText } from '../../utils/actions';

export class ThemeSection extends BasePage {
	async isDisplayed() {
		await waitForElementByText( 'h2', 'Choose a theme' );
		await waitForElementByText( 'button', 'All themes' );
	}

	async continueWithActiveTheme() {
		await this.clickButtonWithText( 'Continue with my active theme' );
	}
}
