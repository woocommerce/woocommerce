import { Page } from 'puppeteer';
import { getElementByText } from '../utils/actions';
import { WP_ADMIN_START_PROFILE_WIZARD } from '../utils/constants';
import { BenefitsSection } from './onboarding/BenefitsSection';
import { BusinessSection } from './onboarding/BusinessSection';
import { IndustrySection } from './onboarding/IndustrySection';
import { ProductTypeSection } from './onboarding/ProductTypesSection';
import { StoreDetailsSection } from './onboarding/StoreDetailsSection';
import { ThemeSection } from './onboarding/ThemeSection';

export class OnboardingWizard {
	page: Page;
	storeDetails: StoreDetailsSection;
	industry: IndustrySection;
	productTypes: ProductTypeSection;
	business: BusinessSection;
	themes: ThemeSection;
	benefits: BenefitsSection;

	constructor( page: Page ) {
		this.page = page;
		this.storeDetails = new StoreDetailsSection( page );
		this.industry = new IndustrySection( page );
		this.productTypes = new ProductTypeSection( page );
		this.business = new BusinessSection( page );
		this.themes = new ThemeSection( page );
		this.benefits = new BenefitsSection( page );
	}

	async start() {
		await this.page.goto( WP_ADMIN_START_PROFILE_WIZARD, {
			waitUntil: 'networkidle0',
		} );
	}

	async continue() {
		const button = await getElementByText( 'button', 'Continue' );
		await button?.click();
	}

	async optionallySelectUsageTracking( select = false ) {
		const usageTrackingHeader = await this.page.waitForSelector(
			'.components-modal__header-heading',
			{
				timeout: 2000,
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
			const button = await getElementByText(
				'button',
				'Yes, count me in'
			);
			await button?.click();
		} else {
			const button = await getElementByText( 'button', 'No thanks' );
			await button?.click();
		}
		this.page.waitForNavigation( {
			waitUntil: 'networkidle0',
			timeout: 2000,
		} );
	}
}
