/**
 * External dependencies
 */
import {
	setCheckbox,
	unsetCheckbox,
	verifyCheckboxIsSet,
	verifyCheckboxIsUnset,
} from '@woocommerce/e2e-utils';
/**
 * Internal dependencies
 */
import { BasePage } from '../../pages/BasePage';
import { waitForElementByText } from '../../utils/actions';

export class BusinessSection extends BasePage {
	async isDisplayed(): Promise< void > {
		await waitForElementByText( 'h2', 'Tell us about your business' );
	}

	async freeFeaturesIsDisplayed(): Promise< void > {
		await waitForElementByText( 'h2', 'Included business features' );
	}

	async selectProductNumber( productLabel: string ): Promise< void > {
		const howManyProductsDropdown = this.getDropdownField(
			'.woocommerce-profile-wizard__product-count'
		);

		await howManyProductsDropdown.select( productLabel );
	}

	async selectCurrentlySelling( currentlySelling: string ): Promise< void > {
		const sellingElsewhereDropdown = this.getDropdownField(
			'.woocommerce-profile-wizard__selling-venues'
		);

		await sellingElsewhereDropdown.select( currentlySelling );
	}
	async selectEmployeesNumber( employeesNumber: string ) {
		const employeesNumberDropdown = this.getDropdownField(
			'.woocommerce-profile-wizard__number-employees'
		);

		await employeesNumberDropdown.select( employeesNumber );
	}
	async selectRevenue( revenue: string ) {
		const revenueDropdown = this.getDropdownField(
			'.woocommerce-profile-wizard__revenue'
		);

		await revenueDropdown.select( revenue );
	}
	async selectOtherPlatformName( otherPlatformName: string ) {
		const otherPlatformNameDropdown = this.getDropdownField(
			'.woocommerce-profile-wizard__other-platform'
		);

		await otherPlatformNameDropdown.select( otherPlatformName );
	}

	async selectInstallFreeBusinessFeatures(
		select: boolean
	): Promise< void > {
		if ( select ) {
			await setCheckbox( '#woocommerce-business-extensions__checkbox' );
		} else {
			await unsetCheckbox( '#woocommerce-business-extensions__checkbox' );
		}
	}

	async expandRecommendedBusinessFeatures(): Promise< void > {
		const expandButtonSelector =
			'.woocommerce-admin__business-details__selective-extensions-bundle__expand';

		await this.page.waitForSelector(
			expandButtonSelector + ':not([disabled])'
		);
		await this.click( expandButtonSelector );

		// Confirm that expanding the list shows all the extensions available to install.
		await this.page.waitForFunction( () => {
			const inputsNum = document.querySelectorAll(
				'.components-checkbox-control__input'
			).length;
			return inputsNum > 1;
		} );
	}

	async uncheckAllRecommendedBusinessFeatures(): Promise< void > {
		await this.unsetAllCheckboxes( '.components-checkbox-control__input' );
	}

	// The old list displayed on the dropdown page
	async uncheckBusinessFeatures(): Promise< void > {
		await this.unsetAllCheckboxes(
			'.woocommerce-profile-wizard__benefit .components-form-toggle__input'
		);
	}

	async selectSetupForClient(): Promise< void > {
		await setCheckbox( '.components-checkbox-control__input' );
	}

	async checkClientSetupCheckbox( selected: boolean ): Promise< void > {
		if ( selected ) {
			await verifyCheckboxIsSet( '.components-checkbox-control__input' );
		} else {
			await verifyCheckboxIsUnset(
				'.components-checkbox-control__input'
			);
		}
	}
}
