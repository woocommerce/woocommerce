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

	async continueWithTheme( themeTitle: string ): Promise< void > {
		const title = await waitForElementByText( 'h2', themeTitle );
		const card = await title?.evaluateHandle( ( element ) => {
			return element.closest( '.components-card' );
		} );
		const chooseButton = await card
			?.asElement()
			?.$x( `//button[contains(text(), "Choose")]` );
		if ( chooseButton && chooseButton.length > 0 ) {
			await chooseButton[ 0 ].click();
		}
	}
}
