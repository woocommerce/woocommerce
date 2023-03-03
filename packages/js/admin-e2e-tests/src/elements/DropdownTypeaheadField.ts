/**
 * External dependencies
 */
import { clearAndFillInput } from '@woocommerce/e2e-utils';
/**
 * Internal dependencies
 */
import { BaseElement } from './BaseElement';

export class DropdownTypeaheadField extends BaseElement {
	async search( text: string ): Promise< void > {
		await clearAndFillInput( this.selector + '-0__control-input', text );
	}
	async select( selector: string ): Promise< void > {
		await this.page.click( this.selector + `__option-0-${ selector }` );
	}

	async checkSelected( value: string ): Promise< void > {
		const selector = this.selector + '-0__control-input';
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
