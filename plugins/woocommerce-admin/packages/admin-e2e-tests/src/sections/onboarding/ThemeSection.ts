/**
 * Internal dependencies
 */
import { BasePage } from '../../pages/BasePage';
import { waitForElementByText } from '../../utils/actions';

export class ThemeSection extends BasePage {
	async isDisplayed(): Promise< void > {
		await waitForElementByText( 'h2', 'Choose a theme' );
		await waitForElementByText( 'button', 'All themes' );
	}

	async continueWithActiveTheme(): Promise< void > {
		await this.clickButtonWithText( 'Continue with my active theme' );
	}
}
