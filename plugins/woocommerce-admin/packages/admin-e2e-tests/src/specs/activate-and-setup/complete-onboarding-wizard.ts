/**
 * Internal dependencies
 */
import { OnboardingWizard } from '../../pages/OnboardingWizard';
import { WcHomescreen } from '../../pages/WcHomescreen';
import { TaskTitles } from '../../constants/taskTitles';
import { Login } from '../../pages/Login';
import { WcSettings } from '../../pages/WcSettings';
import { ProductsSetup } from '../../pages/ProductsSetup';
import { deactivateAndDeleteExtension } from '../../utils/actions';

/* eslint-disable @typescript-eslint/no-var-requires */
const {
	afterAll,
	beforeAll,
	describe,
	it,
	expect,
} = require( '@jest/globals' );
const config = require( 'config' );

const { verifyValueOfInputField } = require( '@woocommerce/e2e-utils' );
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
			await profileWizard.industry.isDisplayed( 7, 8 );
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

		it( 'can select the right currency on settings page related to the onboarding country', async () => {
			const settingsScreen = new WcSettings( page );
			await settingsScreen.navigate();
			verifyValueOfInputField( '#woocommerce_currency', 'USD' );
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

		it( 'can select the right currency on settings page related to the onboarding country', async () => {
			const settingsScreen = new WcSettings( page );
			await settingsScreen.navigate();
			verifyValueOfInputField( '#woocommerce_currency', 'JPY' );
		} );
	} );
};

const testDifferentStoreCurrenciesWCPay = () => {
	const testCountryCurrencyPairs = [
		{
			countryRegionSubstring: 'australia',
			countryRegionSelector: 'AU\\:QLD',
			countryRegion: 'Australia — Queensland',
			expectedCurrency: 'AUD',
			isWCPaySupported: true,
		},
		{
			countryRegionSubstring: 'canada',
			countryRegionSelector: 'CA\\:QC',
			countryRegion: 'Canada — Quebec',
			expectedCurrency: 'CAD',
			isWCPaySupported: true,
		},
		{
			countryRegionSubstring: 'china',
			countryRegionSelector: 'CN\\:CN2',
			countryRegion: 'China — Beijing',
			expectedCurrency: 'CNY',
			isWCPaySupported: false,
		},
		{
			countryRegionSubstring: 'spain',
			countryRegionSelector: 'ES\\:CO',
			countryRegion: 'Spain — Córdoba',
			expectedCurrency: 'EUR',
			isWCPaySupported: true,
		},
		{
			countryRegionSubstring: 'india',
			countryRegionSelector: 'IN\\:DL',
			countryRegion: 'India — Delhi',
			expectedCurrency: 'INR',
			isWCPaySupported: false,
		},
		{
			countryRegionSubstring: 'kingd',
			countryRegionSelector: 'GB',
			countryRegion: 'United Kingdom (UK)',
			expectedCurrency: 'GBP',
			isWCPaySupported: true,
		},
	];

	testCountryCurrencyPairs.forEach( ( spec ) => {
		describe( 'A store can onboard with any country and have the correct currency selected after onboarding.', () => {
			const profileWizard = new OnboardingWizard( page );
			const login = new Login( page );

			beforeAll( async () => {
				await login.login();
			} );
			afterAll( async () => {
				await login.logout();
			} );

			it( `can complete the profile wizard with selecting ${ spec.countryRegion } as the country`, async () => {
				await profileWizard.navigate();
				await profileWizard.storeDetails.completeStoreDetailsSection( {
					countryRegionSubstring: spec.countryRegionSubstring,
					countryRegionSelector: spec.countryRegionSelector,
					countryRegion: spec.countryRegion,
				} );

				// Wait for "Continue" button to become active
				await profileWizard.continue();

				// Wait for usage tracking pop-up window to appear
				await profileWizard.optionallySelectUsageTracking();
				// Query for the industries checkboxes
				await profileWizard.industry.isDisplayed();
				await profileWizard.industry.uncheckIndustries();
				await profileWizard.industry.selectIndustry( 'Other' );
				await profileWizard.continue();
				await profileWizard.productTypes.isDisplayed( 7 );
				await profileWizard.productTypes.uncheckProducts();
				await profileWizard.productTypes.selectProduct(
					'Physical products'
				);
				await profileWizard.productTypes.selectProduct( 'Downloads' );

				await profileWizard.continue();
				await page.waitForNavigation( {
					waitUntil: 'networkidle0',
				} );
				await profileWizard.business.isDisplayed();

				await profileWizard.business.selectProductNumber(
					config.get( 'onboardingwizard.numberofproducts' )
				);
				await profileWizard.business.selectCurrentlySelling(
					config.get( 'onboardingwizard.sellingelsewhere' )
				);

				await profileWizard.continue();
				await profileWizard.business.freeFeaturesIsDisplayed();
				// Add WC Pay check
				await profileWizard.business.expandRecommendedBusinessFeatures();

				if ( spec.isWCPaySupported ) {
					expect( page ).toMatchElement( 'a', {
						text: 'WooCommerce Payments',
					} );
				} else {
					expect( page ).not.toMatchElement( 'a', {
						text: 'WooCommerce Payments',
					} );
				}

				await profileWizard.business.uncheckAllRecommendedBusinessFeatures();
				await profileWizard.continue();
				await profileWizard.themes.isDisplayed();

				//  This navigates to the home screen
				await profileWizard.themes.continueWithActiveTheme();
			} );

			it( `can select ${ spec.expectedCurrency } as the currency for ${ spec.countryRegion }`, async () => {
				const settingsScreen = new WcSettings( page );
				await settingsScreen.navigate();
				verifyValueOfInputField(
					'#woocommerce_currency',
					spec.expectedCurrency
				);
			} );
		} );
	} );
};

const testSubscriptionsInclusion = () => {
	describe( 'A non-US store will not see the Subscriptions inclusion', () => {
		const profileWizard = new OnboardingWizard( page );
		const login = new Login( page );

		beforeAll( async () => {
			await login.login();
		} );

		it( 'can complete the store details section', async () => {
			await profileWizard.navigate();
			await profileWizard.storeDetails.completeStoreDetailsSection( {
				countryRegionSubstring: 'fran',
				countryRegionSelector: 'FR',
				countryRegion: 'France',
			} );

			// Wait for "Continue" button to become active
			await profileWizard.continue();

			// Wait for usage tracking pop-up window to appear
			await profileWizard.optionallySelectUsageTracking();
		} );

		it( 'can complete the product types section, Subscriptions copy is not visible', async () => {
			// Query for the industries checkboxes
			await profileWizard.industry.isDisplayed();
			await profileWizard.industry.uncheckIndustries();
			await profileWizard.industry.selectIndustry( 'Health and beauty' );
			await profileWizard.continue();
			await profileWizard.productTypes.isDisplayed( 7 );
			await profileWizard.productTypes.uncheckProducts();
			await profileWizard.productTypes.selectProduct( 'Subscriptions' );
			await expect( page ).not.toMatchElement( 'p', {
				text:
					'The following extensions will be added to your site for free: WooCommerce Payments. An account is required to use this feature.',
			} );

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

		it( 'should display the WooCommerce Payments extension after it has been installed', async () => {
			await profileWizard.business.freeFeaturesIsDisplayed();
			await profileWizard.business.expandRecommendedBusinessFeatures();

			expect( page ).toMatchElement( 'a', {
				text: 'WooCommerce Payments',
			} );
		} );

		it( 'should display the task "Add Subscriptions to my store"', async () => {
			await profileWizard.goToOBWStep( 'Store Details' );
			await profileWizard.skipStoreSetup();
			const homescreen = new WcHomescreen( page );
			await homescreen.isDisplayed();
			await homescreen.possiblyDismissWelcomeModal();
			const tasks = await homescreen.getTaskList();
			expect( tasks ).toContain( 'Add Subscriptions to my store' );
		} );

		it( 'can select the Subscription option in the "Start with a template" modal', async () => {
			const productsSetup = new ProductsSetup( page );
			await productsSetup.navigate();
			await productsSetup.isDisplayed();
			await productsSetup.clickStartWithTemplate();
			await productsSetup.isStartWithATemplateDisplayed( 3 );
		} );
	} );
	describe( 'A US store will see the Subscriptions inclusion', () => {
		const profileWizard = new OnboardingWizard( page );
		const login = new Login( page );

		afterAll( async () => {
			await deactivateAndDeleteExtension( 'woocommerce-payments' );
			await login.logout();
		} );

		it( 'can complete the store details section', async () => {
			await profileWizard.navigate();
			await profileWizard.storeDetails.completeStoreDetailsSection( {
				countryRegionSubstring: 'cali',
				countryRegionSelector: 'US\\:CA',
				countryRegion: 'United States (US) — California',
			} );

			// Wait for "Continue" button to become active
			await profileWizard.continue();

			// Wait for usage tracking pop-up window to appear
			await profileWizard.optionallySelectUsageTracking();
		} );

		it( 'can complete the product types section, the Subscriptions copy now is visible', async () => {
			// Query for the industries checkboxes
			await profileWizard.industry.isDisplayed();
			await profileWizard.industry.uncheckIndustries();
			await profileWizard.industry.selectIndustry( 'Health and beauty' );
			await profileWizard.continue();
			await profileWizard.productTypes.isDisplayed( 7 );
			await profileWizard.productTypes.uncheckProducts();
			await profileWizard.productTypes.selectProduct( 'Subscriptions' );
			await expect( page ).toMatchElement( 'p', {
				text:
					'The following extensions will be added to your site for free: WooCommerce Payments. An account is required to use this feature.',
			} );

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

		it( 'cannot see the WooCommerce Payments extension after it has been installed', async () => {
			await profileWizard.business.freeFeaturesIsDisplayed();
			await profileWizard.business.expandRecommendedBusinessFeatures();

			expect( page ).not.toMatchElement( 'a', {
				text: 'WooCommerce Payments',
			} );
		} );

		it( 'should not display the task "Add Subscriptions to my store"', async () => {
			await profileWizard.goToOBWStep( 'Store Details' );
			await profileWizard.skipStoreSetup();
			const homescreen = new WcHomescreen( page );
			await homescreen.isDisplayed();
			await homescreen.possiblyDismissWelcomeModal();
			const tasks = await homescreen.getTaskList();
			expect( tasks ).not.toContain( 'Add Subscriptions to my store' );
		} );

		it( 'can select the Subscription option in the "Start with a template" modal', async () => {
			const productsSetup = new ProductsSetup( page );
			await productsSetup.navigate();
			await productsSetup.isDisplayed();
			await productsSetup.clickStartWithTemplate();
			await productsSetup.isStartWithATemplateDisplayed( 4 );
		} );
	} );
};

const testBusinessDetailsForm = () => {
	describe( 'A store that is selling elsewhere will see the "Number of employees” dropdown menu', () => {
		const profileWizard = new OnboardingWizard( page );
		const login = new Login( page );

		beforeAll( async () => {
			await login.login();
		} );

		afterAll( async () => {
			await deactivateAndDeleteExtension( 'woocommerce-payments' );
			await login.logout();
		} );

		it( 'can complete the store details and product types sections', async () => {
			await profileWizard.navigate();

			// Wait for "Continue" button to become active
			await profileWizard.continue();

			// Wait for usage tracking pop-up window to appear
			await profileWizard.optionallySelectUsageTracking();

			// Query for the industries checkboxes
			await profileWizard.industry.isDisplayed();
			await profileWizard.continue();
			await profileWizard.productTypes.isDisplayed( 7 );
			await profileWizard.productTypes.uncheckProducts();
			// Select Physical
			await profileWizard.productTypes.selectProduct(
				'Physical products'
			);

			await profileWizard.continue();
			await page.waitForNavigation( { waitUntil: 'networkidle0' } );
		} );

		it( 'can complete the business details tab', async () => {
			await profileWizard.business.isDisplayed();

			expect( page ).toMatchElement( 'label', {
				text: 'How many employees do you have?',
			} );

			await profileWizard.business.selectProductNumber(
				config.get( 'onboardingwizard.numberofproducts' )
			);
			await profileWizard.business.selectCurrentlySelling(
				config.get( 'onboardingwizard.sellingOnAnotherPlatform' )
			);
			await profileWizard.business.selectEmployeesNumber(
				config.get( 'onboardingwizard.number_employees' )
			);
			await profileWizard.business.selectRevenue(
				config.get( 'onboardingwizard.revenue' )
			);
			await profileWizard.business.selectOtherPlatformName(
				config.get( 'onboardingwizard.other_platform_name' )
			);

			await profileWizard.continue();
			await profileWizard.business.expandRecommendedBusinessFeatures();
			await profileWizard.business.uncheckAllRecommendedBusinessFeatures();
			await profileWizard.continue();
			await profileWizard.themes.isDisplayed();
		} );
	} );
};

module.exports = {
	testAdminOnboardingWizard,
	testSelectiveBundleWCPay,
	testDifferentStoreCurrenciesWCPay,
	testSubscriptionsInclusion,
	testBusinessDetailsForm,
};
