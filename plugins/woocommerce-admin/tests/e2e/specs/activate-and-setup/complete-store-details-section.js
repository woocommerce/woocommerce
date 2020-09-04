/**
 * @format
 */

/**
 * Internal dependencies
 */
import { verifyCheckboxIsUnset } from '../../utils/actions';
import { waitForSelector } from '../../utils/lib';
const config = require( 'config' );

export async function completeStoreDetailsSection( storeDetails = {} ) {
	// Fill store's address - first line
	await expect( page ).toFill(
		'#inspector-text-control-0',
		storeDetails.addressLine1 ||
			config.get( 'addresses.admin.store.addressfirstline' )
	);

	// Fill store's address - second line
	await expect( page ).toFill(
		'#inspector-text-control-1',
		storeDetails.addressLine2 ||
			config.get( 'addresses.admin.store.addresssecondline' )
	);

	// Type the requested country/region substring or 'cali' in the
	// country/region select, then select the requested country/region
	// substring or 'US:CA'.
	await expect( page ).toFill(
		'#woocommerce-select-control-0__control-input',
		storeDetails.countryRegionSubstring || 'cali'
	);
	await expect( page ).toClick(
		`#woocommerce-select-control__option-0-${
			storeDetails.countryRegionSelector || 'US\\:CA'
		}`
	);

	// Make sure the country/region gets selected correctly, using either the
	// requested country/region or US - California.
	await expect( page ).toMatchElement(
		'#woocommerce-select-control-0__control-input',
		{
			value:
				storeDetails.countryRegion || 'United State (US) - California',
		}
	);

	// Fill the city where the store is located
	await expect( page ).toFill(
		'#inspector-text-control-2',
		storeDetails.city || config.get( 'addresses.admin.store.city' )
	);

	// Fill postcode of the store
	await expect( page ).toFill(
		'#inspector-text-control-3',
		storeDetails.postcode || config.get( 'addresses.admin.store.postcode' )
	);

	// Verify that checkbox next to "I'm setting up a store for a client" is not selected
	await verifyCheckboxIsUnset( '.components-checkbox-control__input' );

	// Wait for "Continue" button to become active
	await waitForSelector( page, 'button.is-primary:not(:disabled)' );

	// Click on "Continue" button to move to the next step
	await page.click( 'button.is-primary', { text: 'Continue' } );

	// Wait for usage tracking pop-up window to appear
	await waitForSelector( page, '.components-modal__header-heading' );
	await expect( page ).toMatchElement( '.components-modal__header-heading', {
		text: 'Build a better WooCommerce',
	} );

	// Query for "Continue" buttons
	const continueButtons = await page.$$( 'button.is-primary' );
	expect( continueButtons ).toHaveLength( 2 );

	await Promise.all( [
		// Click on "Continue" button of the usage pop-up window to move to the next step
		continueButtons[ 1 ].click(),

		// Wait for "In which industry does the store operate?" section to load
		page.waitForNavigation( { waitUntil: 'networkidle0' } ),
	] );
}
