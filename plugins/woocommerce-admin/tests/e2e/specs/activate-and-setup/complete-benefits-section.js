/**
 * @format
 */

import { waitForSelector } from '../../utils/lib';

export async function completeBenefitsSection() {
	// Wait for Benefits section to appear
	await waitForSelector( page, '.woocommerce-profile-wizard__header-title' );

	// Wait for "No thanks" button to become active
	await waitForSelector( page, 'button.is-secondary:not(:disabled)' );

	// Click on "No thanks" button to move to the next step
	await page.click( 'button.is-secondary' );
}
