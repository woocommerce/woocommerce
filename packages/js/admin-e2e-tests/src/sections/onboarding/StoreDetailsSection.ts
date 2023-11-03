/**
 * External dependencies
 */
import {
	clearAndFillInput,
	verifyCheckboxIsSet,
	verifyCheckboxIsUnset,
} from '@woocommerce/e2e-utils';
import config from 'config';
/**
 * Internal dependencies
 */
import { DropdownTypeaheadField } from '../../elements/DropdownTypeaheadField';
import { BasePage } from '../../pages/BasePage';
import { waitForElementByText } from '../../utils/actions';

export interface StoreDetails {
	addressLine1?: string;
	addressLine2?: string;
	countryRegionSubstring?: string;
	countryRegionSelector?: string;
	countryRegion?: string;
	city?: string;
	postcode?: string;
	storeEmail?: string;
}

export class StoreDetailsSection extends BasePage {
	private get countryDropdown(): DropdownTypeaheadField {
		return this.getDropdownTypeahead( '#woocommerce-select-control' );
	}

	async isDisplayed(): Promise< void > {
		await waitForElementByText( 'h2', 'Welcome to WooCommerce' );
	}

	async completeStoreDetailsSection(
		storeDetails: StoreDetails = {}
	): Promise< void > {
		// const onboardingWizard = new OnboardingWizard( page );
		// Fill store's address - first line
		await this.fillAddress(
			storeDetails.addressLine1 ||
				config.get( 'addresses.admin.store.addressfirstline' )
		);

		// Type the requested country/region substring or 'cali' in the
		// country/region select, then select the requested country/region
		// substring or 'US:CA'.
		await this.selectCountry(
			storeDetails.countryRegionSubstring || 'cali',
			storeDetails.countryRegionSelector || 'US\\:CA'
		);

		if ( storeDetails.countryRegion ) {
			await this.checkCountrySelected( storeDetails.countryRegion );
		}

		// Fill the city where the store is located
		await this.fillCity(
			storeDetails.city || config.get( 'addresses.admin.store.city' )
		);

		// Fill postcode of the store
		await this.fillPostalCode(
			storeDetails.postcode ||
				config.get( 'addresses.admin.store.postcode' )
		);

		// Fill store's email address
		await this.fillEmailAddress(
			storeDetails.storeEmail ||
				config.get( 'addresses.admin.store.email' )
		);

		// Verify that the marketing checkbox is opt-in by default (WordPress.org Plugin Review Team requirement).
		await this.checkMarketingCheckbox( false );
	}

	async fillAddress( address: string ): Promise< void > {
		await clearAndFillInput( '#inspector-text-control-0', address );
	}

	async selectCountry( search: string, selector: string ): Promise< void > {
		await this.countryDropdown.search( search );
		await this.countryDropdown.select( selector );
	}

	async checkCountrySelected( country: string ): Promise< void > {
		await this.countryDropdown.checkSelected( country );
	}

	async fillCity( city: string ): Promise< void > {
		await clearAndFillInput( '#inspector-text-control-2', city );
	}

	async fillPostalCode( postalCode: string ): Promise< void > {
		await clearAndFillInput( '#inspector-text-control-1', postalCode );
	}

	async fillEmailAddress( email: string ): Promise< void > {
		await clearAndFillInput( '#inspector-text-control-3', email );
	}

	async checkMarketingCheckbox( selected: boolean ): Promise< void > {
		if ( selected ) {
			await verifyCheckboxIsSet( '.components-checkbox-control__input' );
		} else {
			await verifyCheckboxIsUnset(
				'.components-checkbox-control__input'
			);
		}
	}
}
