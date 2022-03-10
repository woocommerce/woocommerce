/**
 * External dependencies
 */
import { Page } from 'puppeteer';

/**
 * Internal dependencies
 */
import { BusinessSection } from '../sections/onboarding/BusinessSection';
import { IndustrySection } from '../sections/onboarding/IndustrySection';
import { ProductTypeSection } from '../sections/onboarding/ProductTypesSection';
import { StoreDetailsSection } from '../sections/onboarding/StoreDetailsSection';
import { ThemeSection } from '../sections/onboarding/ThemeSection';
import { BasePage } from './BasePage';

/* eslint-disable @typescript-eslint/no-var-requires */
const { expect } = require( '@jest/globals' );

export class OnboardingWizard extends BasePage {
	url = 'wp-admin/admin.php?page=wc-admin&path=/setup-wizard';

	storeDetails: StoreDetailsSection;
	industry: IndustrySection;
	productTypes: ProductTypeSection;
	business: BusinessSection;
	themes: ThemeSection;

	constructor( page: Page ) {
		super( page );
		this.storeDetails = new StoreDetailsSection( page );
		this.industry = new IndustrySection( page );
		this.productTypes = new ProductTypeSection( page );
		this.business = new BusinessSection( page );
		this.themes = new ThemeSection( page );
	}

	async skipStoreSetup() {
		await this.clickButtonWithText( 'Skip setup store details' );
		await this.optionallySelectUsageTracking( false );
	}

	async continue() {
		await this.clickButtonWithText( 'Continue' );
	}

	async optionallySelectUsageTracking( select = false ) {
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

	async goToOBWStep( step: string ) {
		await this.clickElementWithText( 'span', step );
	}
}
