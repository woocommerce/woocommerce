/**
 * External dependencies
 */
import type { ElementHandle } from 'puppeteer';
/**
 * Internal dependencies
 */
import { BaseElement } from './BaseElement';
import { hasClass } from '../utils/actions';

export class FormToggle extends BaseElement {
	// Represents a FormToggle input. Use `selector` to represent the container its found in.
	async switchOn(): Promise< void > {
		const container = await this.getCheckboxContainer();
		if ( container && ! ( await hasClass( container, 'is-checked' ) ) ) {
			const input = await this.getCheckboxInput();

			if ( ! input ) {
				throw new Error(
					`Could not find form toggle with selector ${ this.selector }`
				);
			}
			input.click();

			// Wait for it to be checked.
			await this.page.waitForSelector(
				`${ this.selector } .components-form-toggle.is-checked`
			);
		}
	}

	async switchOff(): Promise< void > {
		const container = await this.getCheckboxContainer();
		if ( container && ( await hasClass( container, 'is-checked' ) ) ) {
			const input = await this.getCheckboxInput();

			if ( ! input ) {
				throw new Error(
					`Could not find form toggle with selector ${ this.selector }`
				);
			}
			input.click();

			// Wait for a not checked toggle to be present.
			await page.waitForFunction(
				( selector ) => {
					return document.querySelectorAll( selector ).length;
				},
				{},
				`${ this.selector } .components-form-toggle:not(.is-checked)`
			);
		}
	}

	async getCheckboxContainer(): Promise< ElementHandle< Element > | null > {
		return this.page.$( `${ this.selector } .components-form-toggle` );
	}

	async getCheckboxInput(): Promise< ElementHandle< Element > | null > {
		return this.page.$(
			`${ this.selector } .components-form-toggle__input`
		);
	}

	async isEnabled(): Promise< void > {
		await this.page.waitForSelector(
			`${ this.selector } .components-form-toggle.is-checked`
		);
	}
}
