/**
 * External dependencies
 */
import config from 'config';
import { Page } from 'puppeteer';

/**
 * Internal dependencies
 */
import { BusinessSection } from '../sections/onboarding/BusinessSection';
import { IndustrySection } from '../sections/onboarding/IndustrySection';
import { ProductTypeSection } from '../sections/onboarding/ProductTypesSection';
import {
	StoreDetails,
	StoreDetailsSection,
} from '../sections/onboarding/StoreDetailsSection';
import { BasePage } from './BasePage';

export class OnboardingWizard extends BasePage {
	url = 'wp-admin/admin.php?page=wc-admin&path=/setup-wizard';

	storeDetails: StoreDetailsSection;
	industry: IndustrySection;
	productTypes: ProductTypeSection;
	business: BusinessSection;

	constructor( page: Page ) {
		super( page );
		this.storeDetails = new StoreDetailsSection( page );
		this.industry = new IndustrySection( page );
		this.productTypes = new ProductTypeSection( page );
		this.business = new BusinessSection( page );
	}

	async skipStoreSetup(): Promise< void > {
		await this.clickButtonWithText( 'Skip setup store details' );
		await this.optionallySelectUsageTracking( false );
	}

	async continue(): Promise< void > {
		await this.clickButtonWithText( 'Continue' );
	}

	async optionallySelectUsageTracking( select = false ): Promise< void > {
		const usageTrackingHeader = await this.page.waitForSelector(
			'.components-modal__header-heading',
			{
				timeout: 5000,
			}
		);
		if ( ! usageTrackingHeader ) {
			return;
		}
		await expect( page ).toMatchElement(
			'.components-modal__header-heading',
			{
				text: 'Build a better WooCommerce',
			}
		);

		// Query for primary buttons: "Continue" and "Yes, count me in"
		const primaryButtons = await this.page.$$( 'button.is-primary' );
		expect( primaryButtons ).toHaveLength( 2 );

		if ( select ) {
			await this.clickButtonWithText( 'Yes, count me in' );
		} else {
			await this.clickButtonWithText( 'No thanks' );
		}

		await this.page.waitForNavigation( {
			waitUntil: 'networkidle0',
			timeout: 4000,
		} );
	}

	async goToOBWStep( step: string ): Promise< void > {
		await this.clickElementWithText( 'span', step );
	}

	async walkThroughAndCompleteOnboardingWizard(
		options: {
			storeDetails?: StoreDetails;
			industries?: string[];
			products?: string[];
			businessDetails?: {
				productNumber: string;
				currentlySelling: string;
			};
		} = {}
	): Promise< void > {
		await this.navigate();
		await this.storeDetails.completeStoreDetailsSection(
			options.storeDetails
		);

		// Wait for "Continue" button to become active
		await this.continue();

		// Wait for usage tracking pop-up window to appear
		await this.optionallySelectUsageTracking();
		// Query for the industries checkboxes
		await this.industry.isDisplayed();
		const industries = options.industries || [ 'Other' ];
		for ( const industry of industries ) {
			await this.industry.selectIndustry( industry );
		}
		await this.continue();
		await this.productTypes.isDisplayed( 7 );
		const products = options.products || [
			'Physical products',
			'Downloads',
		];
		for ( const product of products ) {
			await this.productTypes.selectProduct( product );
		}

		await this.continue();
		await page.waitForNavigation( {
			waitUntil: 'networkidle0',
		} );
		await this.business.isDisplayed();

		const businessDetails = options.businessDetails || {
			productNumber: config.get( 'onboardingwizard.numberofproducts' ),
			currentlySelling: config.get( 'onboardingwizard.sellingelsewhere' ),
		};
		await this.business.selectProductNumber(
			businessDetails.productNumber
		);
		await this.business.selectCurrentlySelling(
			businessDetails.currentlySelling
		);

		await this.continue();
		await this.business.freeFeaturesIsDisplayed();
		await this.business.expandRecommendedBusinessFeatures();
		await this.business.uncheckAllRecommendedBusinessFeatures();

		await this.continue();
	}
}
