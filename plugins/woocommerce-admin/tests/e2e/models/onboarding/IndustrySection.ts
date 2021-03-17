import { Page } from 'puppeteer';
import { setCheckbox } from '@woocommerce/e2e-utils';
import { getElementByText, waitForElementByText } from '../../utils/actions';

export class IndustrySection {
	page: Page;

	constructor( page: Page ) {
		this.page = page;
	}

	async isDisplayed( industryCount?: number ) {
		await waitForElementByText(
			'h2',
			'In which industry does the store operate?'
		);

		if ( industryCount ) {
			const length = await this.page.$$eval(
				'.components-checkbox-control__input',
				( items ) => items.length
			);

			expect( length === industryCount ).toBeTruthy();
		}
	}

	async uncheckIndustries() {
		const industryCheckboxes = await this.page.$$(
			'.components-checkbox-control__input'
		);

		for ( const checkbox of industryCheckboxes ) {
			const checkboxStatus = await (
				await checkbox.getProperty( 'checked' )
			 ).jsonValue();
			if ( checkboxStatus === true ) {
				await checkbox.click();
			}
		}
	}

	async selectIndustry( industryLabel: string ) {
		const checkbox = await getElementByText( 'label', industryLabel );
		await checkbox?.click();
	}
}
