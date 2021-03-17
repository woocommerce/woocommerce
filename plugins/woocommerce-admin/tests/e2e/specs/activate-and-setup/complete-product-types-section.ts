/**
 * @format
 */

/**
 * Internal dependencies
 */
import { OnboardingWizard } from '../../models/OnboardingWizard';

export async function completeProductTypesSection(
	expectedProductTypeCount = 7
) {
	const onboarding = new OnboardingWizard( page );
	await onboarding.productTypes.isDisplayed( expectedProductTypeCount );

	await onboarding.productTypes.uncheckProducts();

	// Select Physical and Downloadable products
	await onboarding.productTypes.selectProduct( 'Physical products' );
	await onboarding.productTypes.selectProduct( 'Downloads' );

	await onboarding.continue();
	await page.waitForNavigation( { waitUntil: 'networkidle0' } );
}
