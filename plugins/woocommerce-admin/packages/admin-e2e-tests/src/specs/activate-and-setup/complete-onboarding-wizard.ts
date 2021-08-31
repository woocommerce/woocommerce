/**
 * Internal dependencies
 */
import { OnboardingWizard } from '../../pages/OnboardingWizard';
import { WcHomescreen } from '../../pages/WcHomescreen';
import { TaskTitles } from '../../constants/taskTitles';
import { Login } from '../../pages/Login';

/* eslint-disable @typescript-eslint/no-var-requires */
const {
	afterAll,
	beforeAll,
	describe,
	it,
	expect,
} = require( '@jest/globals' );
const config = require( 'config' );
/* eslint-enable @typescript-eslint/no-var-requires */

/**
 * This tests a default, happy path for the onboarding wizard.
 */
const testAdminOnboardingWizard = () => {
	describe( 'Store owner can complete onboarding wizard', () => {
		const profileWizard = new OnboardingWizard( page );
		const login = new Login( page );

		beforeAll( async () => {
			await login.login();
		} );
		afterAll( async () => {
			await login.logout();
		} );

		it( 'can start the profile wizard', async () => {
			await profileWizard.navigate();
		} );

		it( 'can complete the store details section', async () => {
			await profileWizard.storeDetails.isDisplayed();
			await profileWizard.storeDetails.completeStoreDetailsSection();
			// Wait for "Continue" button to become active
			await profileWizard.continue();

			// Wait for usage tracking pop-up window to appear
			await profileWizard.optionallySelectUsageTracking();
		} );

		it( 'can complete the industry section', async () => {
			// Query for the industries checkboxes
			await profileWizard.industry.isDisplayed( 8 );
			await profileWizard.industry.uncheckIndustries();

			// Select just "fashion" and "health/beauty" to get the single checkbox business section when
			// filling out details for a US store.
			await profileWizard.industry.selectIndustry(
				'Fashion, apparel, and accessories'
			);
			await profileWizard.industry.selectIndustry( 'Health and beauty' );

			await profileWizard.continue();
		} );

		it( 'can complete the product types section', async () => {
			await profileWizard.productTypes.isDisplayed( 7 );
			await profileWizard.productTypes.uncheckProducts();

			// Select Physical and Downloadable products
			await profileWizard.productTypes.selectProduct(
				'Physical products'
			);
			await profileWizard.productTypes.selectProduct( 'Downloads' );

			await profileWizard.continue();
		} );

		it( 'can complete the business section', async () => {
			await profileWizard.business.isDisplayed();
			await profileWizard.business.selectProductNumber(
				config.get( 'onboardingwizard.numberofproducts' )
			);
			await profileWizard.business.selectCurrentlySelling(
				config.get( 'onboardingwizard.sellingelsewhere' )
			);
			await profileWizard.business.checkClientSetupCheckbox( false );
			await profileWizard.continue();
		} );

		it( 'can unselect all business features and continue', async () => {
			await profileWizard.business.freeFeaturesIsDisplayed();
			// Add WC Pay check
			await profileWizard.business.expandRecommendedBusinessFeatures();

			expect( page ).toMatchElement( 'a', {
				text: 'WooCommerce Payments',
			} );

			await profileWizard.business.uncheckAllRecommendedBusinessFeatures();
			await profileWizard.continue();
		} );

		it( 'can complete the theme selection section', async () => {
			await profileWizard.themes.isDisplayed();
			await profileWizard.themes.continueWithActiveTheme();
		} );
	} );
};

const testSelectiveBundleWCPay = () => {
	describe( 'A japanese store can complete the selective bundle install but does not include WCPay.', () => {
		const profileWizard = new OnboardingWizard( page );
		const login = new Login( page );

		beforeAll( async () => {
			await login.login();
		} );
		afterAll( async () => {
			await login.logout();
		} );

		it( 'can start the profile wizard', async () => {
			await profileWizard.navigate();
		} );

		it( 'can complete the store details section', async () => {
			await profileWizard.storeDetails.completeStoreDetailsSection( {
				countryRegionSubstring: 'japan',
				countryRegionSelector: 'JP\\:JP01',
				countryRegion: 'Japan — Hokkaido',
			} );

			// Wait for "Continue" button to become active
			await profileWizard.continue();

			// Wait for usage tracking pop-up window to appear
			await profileWizard.optionallySelectUsageTracking();
		} );

		// JP:JP01
		it( 'can choose the "Other" industry', async () => {
			// Query for the industries checkboxes
			await profileWizard.industry.isDisplayed();
			await profileWizard.industry.uncheckIndustries();
			await profileWizard.industry.selectIndustry( 'Other' );
			await profileWizard.continue();
		} );

		it( 'can complete the product types section', async () => {
			await profileWizard.productTypes.isDisplayed( 7 );
			await profileWizard.productTypes.uncheckProducts();

			// Select Physical and Downloadable products
			await profileWizard.productTypes.selectProduct(
				'Physical products'
			);
			await profileWizard.productTypes.selectProduct( 'Downloads' );

			await profileWizard.continue();
			await page.waitForNavigation( { waitUntil: 'networkidle0' } );
		} );

		it( 'can complete the business details tab', async () => {
			await profileWizard.business.isDisplayed();

			await profileWizard.business.selectProductNumber(
				config.get( 'onboardingwizard.numberofproducts' )
			);
			await profileWizard.business.selectCurrentlySelling(
				config.get( 'onboardingwizard.sellingelsewhere' )
			);

			await profileWizard.continue();
		} );

		it( 'can choose not to install any extensions', async () => {
			await profileWizard.business.freeFeaturesIsDisplayed();
			// Add WC Pay check
			await profileWizard.business.expandRecommendedBusinessFeatures();

			expect( page ).not.toMatchElement( 'a', {
				text: 'WooCommerce Payments',
			} );

			await profileWizard.business.uncheckAllRecommendedBusinessFeatures();
			await profileWizard.continue();
		} );

		it( 'can finish the rest of the wizard successfully', async () => {
			await profileWizard.themes.isDisplayed();

			//  This navigates to the home screen
			await profileWizard.themes.continueWithActiveTheme();
		} );

		it( 'should display the choose payments task, and not the woocommerce payments task', async () => {
			const homescreen = new WcHomescreen( page );
			await homescreen.isDisplayed();
			await homescreen.possiblyDismissWelcomeModal();
			const tasks = await homescreen.getTaskList();
			expect( tasks ).toContain( TaskTitles.addPayments );
			expect( tasks ).not.toContain( TaskTitles.wooPayments );
		} );
	} );
};

module.exports = {
	testAdminOnboardingWizard,
	testSelectiveBundleWCPay,
};
