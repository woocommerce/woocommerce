import { Page } from 'puppeteer';
import { setCheckbox, unsetCheckbox } from '@woocommerce/e2e-utils';
import { DropdownField } from '../DropdownField';
import { waitForElementByText } from '../../utils/actions';

export class BusinessSection {
	page: Page;
	howManyProductsDropdown: DropdownField;
	sellingElsewhereDropdown: DropdownField;

	constructor( page: Page ) {
		this.page = page;
		this.howManyProductsDropdown = new DropdownField(
			page,
			'.components-card__body > div:nth-child(1)'
		);
		this.sellingElsewhereDropdown = new DropdownField(
			page,
			'.components-card__body > div:nth-child(2)'
		);
	}

	async isDisplayed() {
		await waitForElementByText( 'h2', 'Tell us about your business' );
	}

	async freeFeaturesIsDisplayed() {
		await waitForElementByText( 'h2', 'Included business features' );
	}

	async selectProductNumber( productLabel: string ) {
		await this.howManyProductsDropdown.select( productLabel );
	}

	async selectCurrentlySelling( currentlySelling: string ) {
		await this.sellingElsewhereDropdown.select( currentlySelling );
	}

	async selectInstallFreeBusinessFeatures( select: boolean ) {
		if ( select ) {
			await setCheckbox( '#woocommerce-business-extensions__checkbox' );
		} else {
			await unsetCheckbox( '#woocommerce-business-extensions__checkbox' );
		}
	}

	async expandRecommendedBusinessFeatures() {
		const expandButtonSelector =
			'.woocommerce-admin__business-details__selective-extensions-bundle__expand';
		await this.page.waitForSelector( expandButtonSelector );
		await this.page.click( expandButtonSelector );

		// Confirm that expanding the list shows all the extensions available to install.
		await this.page.waitForFunction( () => {
			const inputsNum = document.querySelectorAll(
				'.components-checkbox-control__input'
			).length;
			return inputsNum > 6;
		} );
	}

	async uncheckAllRecommendedBusinessFeatures() {
		const allCheckboxes = await this.page.$$(
			'.components-checkbox-control__input'
		);

		// Uncheck all checkboxes, to avoid installing plugins
		for ( const checkbox of allCheckboxes ) {
			const checkboxStatus = await (
				await checkbox.getProperty( 'checked' )
			 ).jsonValue();
			if ( checkboxStatus === true ) {
				await checkbox.click();
			}
		}
	}

	// The old list displayed on the dropdown page
	async uncheckBusinessFeatures() {
		// checkbox is present, uncheck it.
		const installFeaturesCheckboxes = await page.$$(
			'.woocommerce-profile-wizard__benefit .components-form-toggle__input'
		);
		// Uncheck all checkboxes, to avoid installing plugins
		for ( const checkbox of installFeaturesCheckboxes ) {
			const checkboxStatus = await (
				await checkbox.getProperty( 'checked' )
			 ).jsonValue();
			if ( checkboxStatus === true ) {
				await checkbox.click();
			}
		}
	}
}
