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
import { OnboardingWizard } from '../../models/OnboardingWizard';
import { WcHomescreen } from '../../models/WcHomescreen';
import { TaskTitles } from '../../constants/taskTitles';
import { getElementByText } from '../../utils/actions';

/**
 * This tests a default, happy path for the onboarding wizard.
 */
describe( 'Store owner can complete onboarding wizard', () => {
	it( 'can log in', StoreOwnerFlow.login );
	it( 'can start the profile wizard', StoreOwnerFlow.startProfileWizard );
	it( 'can complete the store details section', async () =>
		await completeStoreDetailsSection() );
	it( 'can complete the industry section', async () =>
		await completeIndustrySection() );
	it( 'can complete the product types section', async () =>
		await completeProductTypesSection() );
	it( 'can complete the business section', async () =>
		await completeSelectiveBundleInstallBusinessDetailsTab() );
	it( 'can unselect all business features and contine', async () => {
		const onboarding = new OnboardingWizard( page );

		await onboarding.business.freeFeaturesIsDisplayed();
		// Add WC Pay check
		await onboarding.business.expandRecommendedBusinessFeatures();

		expect( page ).toMatchElement( 'a', {
			text: 'WooCommerce Payments',
		} );

		await onboarding.business.uncheckAllRecommendedBusinessFeatures();

		await onboarding.continue();
	} );
	it(
		'can complete the theme selection section',
		completeThemeSelectionSection
	);
} );

/**
 * A non-US store doesn't get the "install recommended features" checkbox.
 */
describe( 'A spanish store does not get the install recommended features tab, but sees the benefits section', () => {
	it( 'can log in', StoreOwnerFlow.login );
	it( 'can start the profile wizard', StoreOwnerFlow.startProfileWizard );
	it( 'can complete the store details section', async () => {
		await completeStoreDetailsSection( {
			countryRegionSubstring: 'spain',
			countryRegionSelector: 'ES\\:B',
			countryRegion: 'Spain - Barcelona',
		} );
	} );
	it( 'can complete the industry section', async () => {
		await completeIndustrySection( 7 );
	} );
	it( 'can complete the product types section', async () =>
		await completeProductTypesSection() );
	it( 'does not have the install recommended features checkbox', async () => {
		const installFeaturesCheckbox = await page.$(
			'#woocommerce-business-extensions__checkbox'
		);

		expect( installFeaturesCheckbox ).toBe( null );
	} );
	it( 'can complete the business section', async () =>
		await completeBusinessSection() );
	it( 'can complete the theme selection section', async () =>
		await completeThemeSelectionSection() );
	it( 'can complete the benefits section', async () => {
		const onboarding = new OnboardingWizard( page );
		await onboarding.benefits.isDisplayed();
		await onboarding.benefits.noThanks();
	} );
	it( 'should display the choose payments task, and not the woocommerce payments task', async () => {
		const homescreen = new WcHomescreen( page );
		await homescreen.isDisplayed();
		await homescreen.possiblyDismissWelcomeModal();
		const tasks = await homescreen.getTaskList();
		expect( tasks ).toContain( TaskTitles.addPayments );
		expect( tasks ).not.toContain( TaskTitles.wooPayments );
	} );

	it( 'should not display woocommerce payments as a payments option', async () => {
		const homescreen = new WcHomescreen( page );
		await homescreen.clickOnTaskList( TaskTitles.addPayments );
		const wcPayLabel = await getElementByText(
			'h2',
			'WooCommerce Payments'
		);
		expect( wcPayLabel ).toBeUndefined();
	} );
} );

describe( 'A japanese store can complete the selective bundle install but does not include WCPay. ', () => {
	it( 'can log in', StoreOwnerFlow.login );
	it( 'can start the profile wizard', StoreOwnerFlow.startProfileWizard );
	it( 'can complete the store details section', async () => {
		await completeStoreDetailsSection( {
			countryRegionSubstring: 'japan',
			countryRegionSelector: 'JP\\:JP01',
			countryRegion: 'Japan — Hokkaido',
		} );
	} );
	// JP:JP01
	it( 'can choose the "Other" industry', async () => {
		await chooseIndustries( [ 'Other' ] );
	} );
	it( 'can complete the product types section', async () =>
		await completeProductTypesSection() );
	it( 'can complete the business details tab', async () => {
		await completeSelectiveBundleInstallBusinessDetailsTab();
	} );

	it( 'can choose not to install any extensions', async () => {
		const onboarding = new OnboardingWizard( page );

		await onboarding.business.freeFeaturesIsDisplayed();
		// Add WC Pay check
		await onboarding.business.expandRecommendedBusinessFeatures();

		expect( page ).not.toMatchElement( 'a', {
			text: 'WooCommerce Payments',
		} );

		await onboarding.business.uncheckAllRecommendedBusinessFeatures();

		await onboarding.continue();
	} );

	it(
		'can finish the rest of the wizard successfully',
		completeThemeSelectionSection
	);
	it( 'should display the choose payments task, and not the woocommerce payments task', async () => {
		const homescreen = new WcHomescreen( page );
		await homescreen.isDisplayed();
		await homescreen.possiblyDismissWelcomeModal();
		const tasks = await homescreen.getTaskList();
		expect( tasks ).toContain( TaskTitles.addPayments );
		expect( tasks ).not.toContain( TaskTitles.wooPayments );
	} );
} );
