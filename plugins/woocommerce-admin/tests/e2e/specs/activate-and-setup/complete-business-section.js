/**
 * @format
 */

/**
 * Internal dependencies
 */
import {
	setCheckboxToUnchecked,
	clickContinue,
	getElementByText,
} from './utils';
import { waitForElementCount } from '../../utils/lib';
const config = require( 'config' );

async function fillOutDropdowns() {
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
	const value = await page.evaluate( () => {
		const inputs = document.querySelectorAll(
			'.woocommerce-select-control .woocommerce-select-control__control-input'
		);
		return inputs[ 1 ].value;
	} );
	if ( value !== config.get( 'onboardingwizard.sellingelsewhere' ) ) {
		await selectControls[ 1 ].click();
		await page.waitForSelector( '.woocommerce-select-control__listbox' );
		await expect( page ).toClick( '.woocommerce-select-control__option', {
			text: config.get( 'onboardingwizard.sellingelsewhere' ),
		} );
	}
}

export async function completeBusinessSection() {
	await fillOutDropdowns();

	// Site is in US so the "Install recommended free business features"
	// checkbox is present, uncheck it.
	const installFeaturesCheckboxes = await page.$$(
		'.woocommerce-profile-wizard__benefit .components-form-toggle__input'
	);
	// Uncheck all checkboxes, to avoid installing plugins
	for ( const checkbox of installFeaturesCheckboxes ) {
		await setCheckboxToUnchecked( checkbox );
	}

	await clickContinue();
}

export async function completeSelectiveBundleInstallBusinessDetailsTab() {
	await fillOutDropdowns();

	await page.click( 'button.is-primary' );
}

export async function unselectAllFeaturesAndContinue(
	shouldWCPayBeListed = true
) {
	const expandButtonSelector =
		'.woocommerce-admin__business-details__selective-extensions-bundle__expand';
	await page.waitForSelector( expandButtonSelector );
	await page.click( expandButtonSelector );

	// Confirm that expanding the list shows all the extensions available to install.
	await waitForElementCount(
		page,
		'.components-checkbox-control__input',
		shouldWCPayBeListed ? 10 : 7
	);
	const wcPayLabel = await getElementByText( 'a', 'WooCommerce Payments' );
	if ( shouldWCPayBeListed ) {
		expect( wcPayLabel ).toBeDefined();
	} else {
		expect( wcPayLabel ).toBeUndefined();
	}

	const allCheckboxes = await page.$$(
		'.components-checkbox-control__input'
	);

	// Uncheck all checkboxes, to avoid installing plugins
	for ( const checkbox of allCheckboxes ) {
		await setCheckboxToUnchecked( checkbox );
	}

	await page.click( 'button.is-primary' );
}
