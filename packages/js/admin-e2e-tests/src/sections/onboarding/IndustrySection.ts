/**
 * Internal dependencies
 */
import { BasePage } from '../../pages/BasePage';
import { waitForElementByText } from '../../utils/actions';

export class IndustrySection extends BasePage {
	async isDisplayed(
		industryCount?: number,
		industryCountMax?: number
	): Promise< void > {
		await waitForElementByText(
			'h2',
			'In which industry does the store operate?'
		);

		if ( industryCount ) {
			const length = await this.page.$$eval(
				'.components-checkbox-control__input',
				( items ) => items.length
			);

			if ( industryCountMax ) {
				expect(
					length >= industryCount && length <= industryCountMax
				).toBeTruthy();
			} else {
				expect( length === industryCount ).toBeTruthy();
			}
		}
	}

	async uncheckIndustries(): Promise< void > {
		await this.unsetAllCheckboxes( '.components-checkbox-control__input' );
	}

	async selectIndustry( industryLabel: string ): Promise< void > {
		await this.setCheckboxWithText( industryLabel );
	}
}
