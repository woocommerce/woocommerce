/**
 * @format
 */

/**
 * Internal dependencies
 */
import { OnboardingWizard } from '../../models/OnboardingWizard';

export async function completeThemeSelectionSection() {
	const onboarding = new OnboardingWizard( page );
	// Make sure we're on the theme selection page before clicking continue
	await onboarding.themes.isDisplayed();

	await onboarding.themes.continueWithActiveTheme();
}
