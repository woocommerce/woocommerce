/**
 * Internal dependencies
 */
import { BasePage } from '../../pages/BasePage';
import { waitForElementByText } from '../../utils/actions';

export class ProductTypeSection extends BasePage {
	async isDisplayed( productCount: number ): Promise< void > {
		await waitForElementByText(
			'h2',
			'What type of products will be listed?'
		);
		const length = await this.page.$$eval(
			'.components-checkbox-control__input',
			( items ) => items.length
		);
		expect( length === productCount ).toBeTruthy();
	}

	async uncheckProducts(): Promise< void > {
		await this.unsetAllCheckboxes( '.components-checkbox-control__input' );
	}

	async selectProduct( productLabel: string ): Promise< void > {
		await this.setCheckboxWithText( productLabel );
	}
}
