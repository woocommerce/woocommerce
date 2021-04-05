import { BasePage } from '../../pages/BasePage';
import { waitForElementByText } from '../../utils/actions';

export class BenefitsSection extends BasePage {
	async isDisplayed() {
		await waitForElementByText(
			'h2',
			'Enhance your store with Jetpack and WooCommerce Shipping & Tax'
		);
	}

	async noThanks() {
		// Click on "No thanks" button to move to the next step
		await this.clickButtonWithText( 'No thanks' );
	}
}
