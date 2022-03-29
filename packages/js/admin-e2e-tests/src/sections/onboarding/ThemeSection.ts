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
		const chooseButton = await title?.evaluateHandle( ( element ) => {
			const card = element.closest( '.components-card' );
			return Array.from( card?.querySelectorAll( 'button' ) || [] ).find(
				( el ) => el.textContent === 'Choose'
			);
		} );
		if ( chooseButton ) {
			await chooseButton.asElement()?.click();
		}
	}
}
