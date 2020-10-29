/**
 * @format
 */

/**
 * Internal dependencies
 */
import { setCheckboxToUnchecked, clickContinue } from './utils';
import { waitForElementCount } from '../../utils/lib';
const config = require( 'config' );

export async function completeBusinessSection() {
	// Query for the <SelectControl>s
	await waitForElementCount( page, '.woocommerce-select-control', 2 );
	const selectControls = await page.$$( '.woocommerce-select-control' );

	// Fill the number of products you plan to sell
	await selectControls[ 0 ].click();
	await page.waitForSelector( '.woocommerce-select-control__listbox' );
	await expect( page ).toClick( '.woocommerce-select-control__option', {
		text: config.get( 'onboardingwizard.numberofproducts' ),
	} );

	// Fill currently selling elsewhere
	await selectControls[ 1 ].click();
	await page.waitForSelector( '.woocommerce-select-control__listbox' );
	await expect( page ).toClick( '.woocommerce-select-control__option', {
		text: config.get( 'onboardingwizard.sellingelsewhere' ),
	} );

	// Site is in US so the "Install recommended free business features"
	// checkbox is present, uncheck it.
	const installFeaturesCheckbox = await page.$(
		'#woocommerce-business-extensions__checkbox'
	);
	await setCheckboxToUnchecked( installFeaturesCheckbox );

	await clickContinue();
}
