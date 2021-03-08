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
	unselectAllFeaturesAndContinue,
} from './complete-business-section';
import { completeThemeSelectionSection } from './complete-theme-selection-section';
import { completeBenefitsSection } from './complete-benefits-section';
import {
	clickOnTaskList,
	getTaskList,
	onHomescreen,
	possibleDismissWelcomeModal,
	TaskTitles,
} from './homescreen';
import { getElementByText } from './utils';

/**
 * This tests a default, happy path for the onboarding wizard.
 */
describe( 'Store owner can complete onboarding wizard', () => {
	it( 'can log in', StoreOwnerFlow.login );
	it( 'can start the profile wizard', StoreOwnerFlow.startProfileWizard );
	it( 'can complete the store details section', completeStoreDetailsSection );
	it( 'can complete the industry section', completeIndustrySection );
	it( 'can complete the product types section', completeProductTypesSection );
	it( 'can complete the business section', async () =>
		await completeSelectiveBundleInstallBusinessDetailsTab() );
	it( 'can unselect all business features and contine', async () =>
		await unselectAllFeaturesAndContinue() );
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
	it( 'can complete the product types section', completeProductTypesSection );
	it( 'does not have the install recommended features checkbox', async () => {
		const installFeaturesCheckbox = await page.$(
			'#woocommerce-business-extensions__checkbox'
		);

		expect( installFeaturesCheckbox ).toBe( null );
	} );
	it( 'can complete the business section', async () =>
		await completeBusinessSection() );
	it(
		'can complete the theme selection section',
		completeThemeSelectionSection
	);
	it( 'can complete the benefits section', completeBenefitsSection );
	it( 'should display the choose payments task, and not the woocommerce payments task', async () => {
		await onHomescreen();
		await possibleDismissWelcomeModal();
		const tasks = await getTaskList();
		expect( tasks ).toContain( TaskTitles.addPayments );
		expect( tasks ).not.toContain( TaskTitles.wooPayments );
	} );

	it( 'should not display woocommerce payments as a payments option', async () => {
		const tasks = await getTaskList();
		const index = tasks.indexOf( TaskTitles.addPayments );
		await clickOnTaskList( index );
		await page.waitForFunction(
			'document.querySelector(".woocommerce-layout__header-heading").innerText == "Choose payment methods"'
		);
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
	it( 'can complete the product types section', completeProductTypesSection );
	it( 'can complete the business details tab', async () => {
		await completeSelectiveBundleInstallBusinessDetailsTab();
	} );

	it( 'can choose not to install any extensions', async () => {
		await unselectAllFeaturesAndContinue( false );
	} );

	it(
		'can finish the rest of the wizard successfully',
		completeThemeSelectionSection
	);
	it( 'should display the choose payments task, and not the woocommerce payments task', async () => {
		await onHomescreen();
		await possibleDismissWelcomeModal();
		const tasks = await getTaskList();
		expect( tasks ).toContain( TaskTitles.addPayments );
		expect( tasks ).not.toContain( TaskTitles.wooPayments );
	} );
} );
