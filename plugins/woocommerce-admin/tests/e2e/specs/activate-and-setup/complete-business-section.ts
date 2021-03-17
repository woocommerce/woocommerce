/**
/**
 * Internal dependencies
 */
import { OnboardingWizard } from '../../models/OnboardingWizard';
const config = require( 'config' );

export async function completeBusinessSection() {
	const onboarding = new OnboardingWizard( page );
	await onboarding.business.isDisplayed();

	await onboarding.business.selectProductNumber(
		config.get( 'onboardingwizard.numberofproducts' )
	);
	await onboarding.business.selectCurrentlySelling(
		config.get( 'onboardingwizard.sellingelsewhere' )
	);

	// Site is in US so the "Install recommended free business features"
	await onboarding.business.uncheckBusinessFeatures();

	await onboarding.continue();
}

export async function completeSelectiveBundleInstallBusinessDetailsTab() {
	const onboarding = new OnboardingWizard( page );
	await onboarding.business.isDisplayed();

	await onboarding.business.selectProductNumber(
		config.get( 'onboardingwizard.numberofproducts' )
	);
	await onboarding.business.selectCurrentlySelling(
		config.get( 'onboardingwizard.sellingelsewhere' )
	);

	await onboarding.continue();
}
