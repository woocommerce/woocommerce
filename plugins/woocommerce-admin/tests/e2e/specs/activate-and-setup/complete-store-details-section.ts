/**
 * @format
 */

/**
 * Internal dependencies
 */
import { OnboardingWizard } from '../../models/OnboardingWizard';
const config = require( 'config' );

interface StoreDetails {
	addressLine1?: string;
	addressLine2?: string;
	countryRegionSubstring?: string;
	countryRegionSelector?: string;
	countryRegion?: string;
	city?: string;
	postcode?: string;
}

export async function completeStoreDetailsSection(
	storeDetails: StoreDetails = {}
) {
	const onboardingWizard = new OnboardingWizard( page );
	// Fill store's address - first line
	await onboardingWizard.storeDetails.fillAddress(
		storeDetails.addressLine1 ||
			config.get( 'addresses.admin.store.addressfirstline' )
	);

	// Fill store's address - second line
	await onboardingWizard.storeDetails.fillAddressLineTwo(
		storeDetails.addressLine2 ||
			config.get( 'addresses.admin.store.addresssecondline' )
	);

	// Type the requested country/region substring or 'cali' in the
	// country/region select, then select the requested country/region
	// substring or 'US:CA'.
	await onboardingWizard.storeDetails.selectCountry(
		storeDetails.countryRegionSubstring || 'cali',
		storeDetails.countryRegionSelector || 'US\\:CA'
	);
	if ( storeDetails.countryRegion ) {
		await onboardingWizard.storeDetails.countryDropdown.checkSelected(
			storeDetails.countryRegion
		);
	}

	// Fill the city where the store is located
	await onboardingWizard.storeDetails.fillCity(
		storeDetails.city || config.get( 'addresses.admin.store.city' )
	);

	// Fill postcode of the store
	await onboardingWizard.storeDetails.fillPostalCode(
		storeDetails.postcode || config.get( 'addresses.admin.store.postcode' )
	);

	// Verify that checkbox next to "I'm setting up a store for a client" is not selected
	await onboardingWizard.storeDetails.checkClientSetupCheckbox( false );

	// Wait for "Continue" button to become active
	await onboardingWizard.continue();

	// Wait for usage tracking pop-up window to appear
	await onboardingWizard.optionallySelectUsageTracking();
}
