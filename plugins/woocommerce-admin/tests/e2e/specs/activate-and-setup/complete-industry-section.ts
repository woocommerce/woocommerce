/**
 * @format
 */

/**
 * Internal dependencies
 */

import { OnboardingWizard } from '../../models/OnboardingWizard';

export async function completeIndustrySection( expectedIndustryCount = 8 ) {
	const onboarding = new OnboardingWizard( page );
	// Query for the industries checkboxes
	await onboarding.industry.isDisplayed( expectedIndustryCount );

	await onboarding.industry.uncheckIndustries();

	// Select just "fashion" and "health/beauty" to get the single checkbox business section when
	// filling out details for a US store.
	await onboarding.industry.selectIndustry(
		'Fashion, apparel, and accessories'
	);

	await onboarding.industry.selectIndustry( 'Health and beauty' );

	await onboarding.continue();
}

// Check industry checkboxes based on industryLabels passed in. Note each label must match
// the text contents of the label.
export async function chooseIndustries( industryLabels: string[] = [] ) {
	const onboarding = new OnboardingWizard( page );
	// Query for the industries checkboxes
	await onboarding.industry.isDisplayed();

	await onboarding.industry.uncheckIndustries();

	for ( const labelText of industryLabels ) {
		await onboarding.industry.selectIndustry( labelText );
	}

	await onboarding.continue();
}
