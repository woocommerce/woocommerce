/**
 * @format
 */

export async function completeBenefitsSection() {
	// Wait for Benefits section to appear
	await page.waitForSelector(
		'.woocommerce-profile-wizard__container.benefits'
	);

	// Wait for "No thanks" button to become active
	await page.waitForSelector( 'button.is-secondary:not(:disabled)' );

	// Click on "No thanks" button to move to the next step
	await page.click( 'button.is-secondary' );
}
