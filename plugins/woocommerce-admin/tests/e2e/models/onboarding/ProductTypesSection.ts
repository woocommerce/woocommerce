import { Page } from 'puppeteer';
import { setCheckbox } from '@woocommerce/e2e-utils';
import { getElementByText, waitForElementByText } from '../../utils/actions';

export class ProductTypeSection {
	page: Page;

	constructor( page: Page ) {
		this.page = page;
	}

	async isDisplayed( productCount: number ) {
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

	async uncheckProducts() {
		const productCheckboxes = await this.page.$$(
			'.components-checkbox-control__input'
		);

		for ( const checkbox of productCheckboxes ) {
			const checkboxStatus = await (
				await checkbox.getProperty( 'checked' )
			 ).jsonValue();
			if ( checkboxStatus === true ) {
				await checkbox.click();
			}
		}
	}

	async selectProduct( productLabel: string ) {
		const checkbox = await getElementByText( 'label', productLabel );
		await checkbox?.click();
	}
}
