/**
 * @format
 */

/**
 * Internal dependencies
 */
import { clickContinue } from './utils';

export async function completeThemeSelectionSection() {
	// Make sure we're on the theme selection page before clicking continue
	await page.waitForSelector(
		'.woocommerce-profile-wizard__themes-tab-panel'
	);

	await clickContinue();
}
