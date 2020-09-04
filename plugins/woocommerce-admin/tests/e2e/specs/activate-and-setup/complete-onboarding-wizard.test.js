/**
 * @format
 */

/**
 * Internal dependencies
 */
import { StoreOwnerFlow } from '../../utils/flows';
import { completeStoreDetailsSection } from './complete-store-details-section';
import { completeIndustrySection } from './complete-industry-section';
import { completeProductTypesSection } from './complete-product-types-section';
import { completeBusinessSection } from './complete-business-section';
import { completeThemeSelectionSection } from './complete-theme-selection-section';
import { completeBenefitsSection } from './complete-benefits-section';

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
		await completeIndustrySection( 8 );
	} );
	it( 'can complete the product types section', completeProductTypesSection );
	it( 'does not have the install recommended features checkbox', async () => {
		const installFeaturesCheckbox = await page.$(
			'#woocommerce-business-extensions__checkbox'
		);

		expect( installFeaturesCheckbox ).toBe( null );
	} );
} );
