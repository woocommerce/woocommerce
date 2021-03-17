import { Page } from 'puppeteer';
import {
	clearAndFillInput,
	verifyValueOfInputField,
} from '@woocommerce/e2e-utils';

export class DropdownTypeaheadField {
	page: Page;
	id: string;

	constructor( page: Page, id: string ) {
		this.page = page;
		this.id = id;
	}
	async search( text: string ) {
		await clearAndFillInput( this.id + '-0__control-input', text );
	}
	async select( selector: string ) {
		await this.page.click( this.id + `__option-0-${ selector }` );
	}

	async checkSelected( value: string ) {
		const selector = this.id + '-0__control-input';
		await page.focus( selector );
		const field = await this.page.$( selector );
		const curValue = await field?.getProperty( 'value' );
		if ( curValue ) {
			const fieldValue = ( await curValue.jsonValue() ) as string;
			// Only compare alphanumeric characters
			expect( fieldValue?.replace( /\W/g, '' ) ).toBe(
				value.replace( /\W/g, '' )
			);
		}
	}
}
