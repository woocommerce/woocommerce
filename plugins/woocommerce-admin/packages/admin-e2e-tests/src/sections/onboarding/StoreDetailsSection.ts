/**
 * Internal dependencies
 */
import { DropdownTypeaheadField } from '../../elements/DropdownTypeaheadField';
import { BasePage } from '../../pages/BasePage';
import { waitForElementByText } from '../../utils/actions';

/* eslint-disable @typescript-eslint/no-var-requires */
const {
	clearAndFillInput,
	verifyCheckboxIsSet,
	verifyCheckboxIsUnset,
} = require( '@woocommerce/e2e-utils' );
const config = require( 'config' );
/* eslint-enable @typescript-eslint/no-var-requires */

interface StoreDetails {
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

	async isDisplayed() {
		await waitForElementByText( 'h2', 'Welcome to WooCommerce' );
	}

	async completeStoreDetailsSection( storeDetails: StoreDetails = {} ) {
		// const onboardingWizard = new OnboardingWizard( page );
		// Fill store's address - first line
		await this.fillAddress(
			storeDetails.addressLine1 ||
				config.get( 'addresses.admin.store.addressfirstline' )
		);

		// Fill store's address - second line
		await this.fillAddressLineTwo(
			storeDetails.addressLine2 ||
				config.get( 'addresses.admin.store.addresssecondline' )
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

		// Verify that checkbox next to "Get tips, product updates and inspiration straight to your mailbox" is selected
		await this.checkMarketingCheckbox( true );
	}

	async fillAddress( address: string ) {
		await clearAndFillInput( '#inspector-text-control-0', address );
	}

	async fillAddressLineTwo( address: string ) {
		await clearAndFillInput( '#inspector-text-control-1', address );
	}

	async selectCountry( search: string, selector: string ) {
		await this.countryDropdown.search( search );
		await this.countryDropdown.select( selector );
	}

	async checkCountrySelected( country: string ) {
		await this.countryDropdown.checkSelected( country );
	}

	async fillCity( city: string ) {
		await clearAndFillInput( '#inspector-text-control-2', city );
	}

	async fillPostalCode( postalCode: string ) {
		await clearAndFillInput( '#inspector-text-control-3', postalCode );
	}

	async fillEmailAddress( email: string ) {
		await clearAndFillInput( '#inspector-text-control-4', email );
	}

	async checkMarketingCheckbox( selected: boolean ) {
		if ( selected ) {
			await verifyCheckboxIsSet( '.components-checkbox-control__input' );
		} else {
			await verifyCheckboxIsUnset(
				'.components-checkbox-control__input'
			);
		}
	}
}
