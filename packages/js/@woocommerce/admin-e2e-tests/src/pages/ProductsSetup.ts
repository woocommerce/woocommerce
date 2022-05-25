/**
 * Internal dependencies
 */
import { waitForElementByText } from '../utils/actions';
import { BasePage } from './BasePage';

export class ProductsSetup extends BasePage {
	url = 'wp-admin/admin.php?page=wc-admin&task=products';

	async isDisplayed(): Promise< void > {
		await waitForElementByText( 'h1', 'Add my products' );
	}

	async isStartWithATemplateDisplayed(
		templatesCount: number
	): Promise< void > {
		await waitForElementByText( 'h1', 'Start with a template' );
		const length = await this.page.$$eval(
			'.components-radio-control__input',
			( items ) => items.length
		);
		expect( length === templatesCount ).toBeTruthy();
	}

	async clickStartWithTemplate(): Promise< void > {
		await this.clickElementWithText( '*', 'Start with a template' );
	}
}
