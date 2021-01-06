/**
 * @format
 */

/**
 * Internal dependencies
 */
import { StoreOwnerFlow } from '../../utils/flows';
import { completeStoreDetailsSection } from './complete-store-details-section';
import {
	chooseIndustries,
	completeIndustrySection,
} from './complete-industry-section';
import { completeProductTypesSection } from './complete-product-types-section';
import {
	completeBusinessSection,
	completeSelectiveBundleInstallBusinessDetailsTab,
} from './complete-business-section';
import { completeThemeSelectionSection } from './complete-theme-selection-section';
import { completeBenefitsSection } from './complete-benefits-section';
import { waitForElementCount } from '../../utils/lib';
import { getElementProperty, setCheckboxToUnchecked } from './utils';

/**
 * This tests a default, happy path for the onboarding wizard.
 */
describe( 'Store owner can complete onboarding wizard', () => {
	it( 'can log in', StoreOwnerFlow.login );
	it( 'can start the profile wizard', StoreOwnerFlow.startProfileWizard );
	it( 'can complete the store details section', completeStoreDetailsSection );
	it( 'can complete the industry section', completeIndustrySection );
	it( 'can complete the product types section', completeProductTypesSection );
	it( 'can complete the business section', completeBusinessSection );
	it(
		'can complete the theme selection section',
		completeThemeSelectionSection
	);
	it( 'can complete the benefits section', completeBenefitsSection );
} );

/**
 * A non-US store doesn't get the "install recommended features" checkbox.
 */
describe( 'Non-US store does not get the install recommended features checkbox', () => {
	it( 'can log in', StoreOwnerFlow.login );
	it( 'can start the profile wizard', StoreOwnerFlow.startProfileWizard );
	it( 'can complete the store details section', async () => {
		await completeStoreDetailsSection( {
			countryRegionSubstring: 'australia',
			countryRegionSelector: 'AU\\:QLD',
			countryRegion: 'Australia - Queensland',
		} );
	} );
	it( 'can complete the industry section', async () => {
		await completeIndustrySection( 7 );
	} );
	it( 'can complete the product types section', completeProductTypesSection );
	it( 'does not have the install recommended features checkbox', async () => {
		const installFeaturesCheckbox = await page.$(
			'#woocommerce-business-extensions__checkbox'
		);

		expect( installFeaturesCheckbox ).toBe( null );
	} );
} );

describe( 'A US store with industry "other" can complete the selective bundle install a/b test. ', () => {
	it( 'can log in', StoreOwnerFlow.login );
	it( 'can start the profile wizard', StoreOwnerFlow.startProfileWizard );
	it( 'can complete the store details section', completeStoreDetailsSection );
	it( 'can choose the "Other" industry', async () => {
		await chooseIndustries( [ 'Other' ] );
	} );
	it( 'can complete the product types section', completeProductTypesSection );
	it( 'can complete the business details tab', async () => {
		await completeSelectiveBundleInstallBusinessDetailsTab();
	} );

	it( 'can choose not to install any extensions', async () => {
		const expandButtonSelector =
			'.woocommerce-admin__business-details__selective-extensions-bundle__expand';
		await page.waitForSelector( expandButtonSelector );
		await page.click( expandButtonSelector );

		// Confirm that expanding the list shows all the extensions available to install.
		await waitForElementCount(
			page,
			'.components-checkbox-control__input',
			8
		);

		const allCheckboxes = await page.$$(
			'.components-checkbox-control__input'
		);

		// Uncheck all checkboxes, to avoid installing plugins
		for ( const checkbox of allCheckboxes ) {
			await setCheckboxToUnchecked( checkbox );
		}

		await page.click( 'button.is-primary' );
	} );

	it(
		'can finish the rest of the wizard successfully',
		completeThemeSelectionSection
	);
} );
