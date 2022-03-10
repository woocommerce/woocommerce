/**
 * Internal dependencies
 */
import { getElementByText, getInputValue } from '../utils/actions';
import { BaseElement } from './BaseElement';

export class DropdownField extends BaseElement {
	async select( value: string ) {
		const currentVal = await getInputValue( this.selector + ' input' );
		if ( currentVal !== value ) {
			await this.page.click(
				this.selector + ' .woocommerce-select-control__control'
			);
			const button = await getElementByText(
				'button',
				value,
				this.selector
			);
			await button?.click();
			await this.checkSelected( value );
		}
	}

	async checkSelected( value: string ) {
		const currentVal = await getInputValue( this.selector + ' input' );
		expect( currentVal ).toBe( value );
	}
}
