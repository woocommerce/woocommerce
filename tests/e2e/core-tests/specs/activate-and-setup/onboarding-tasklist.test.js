/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
const {
	merchant,
	completeOnboardingWizard,
} = require( '@woocommerce/e2e-utils' );

/**
 * External dependencies
 */
const {
	it,
	describe,
} = require( '@jest/globals' );

const runOnboardingFlowTest = () => {
	describe('Store owner can go through store Onboarding', () => {

		it('can start and complete onboarding when visiting the site for the first time.', async () => {
			await merchant.runSetupWizard();
			await completeOnboardingWizard();
		});
	});
};

const runTaskListTest = () => {
	describe('Store owner can go through setup Task List', () => {
		it('can setup shipping', async () => {
			await page.evaluate(() => {
				document.querySelector('.woocommerce-list__item-title').scrollIntoView();
			});
			// Query for all tasks on the list
			const taskListItems = await page.$$('.woocommerce-list__item-title');
			expect(taskListItems).toHaveLength(6);

			const [ setupTaskListItem ] = await page.$x( '//div[contains(text(),"Set up shipping")]' );
			await Promise.all([
				// Click on "Set up shipping" task to move to the next step
				setupTaskListItem.click(),

				// Wait for shipping setup section to load
				page.waitForNavigation({waitUntil: 'networkidle0'}),
			]);

			// Wait for "Proceed" button to become active
			await page.waitForSelector('button.is-primary:not(:disabled)');
			await page.waitFor(3000);

			// Click on "Proceed" button to save shipping settings
			await page.click('button.is-primary');
			await page.waitFor(3000);
		});
	});
};

module.exports = {
	runOnboardingFlowTest,
	runTaskListTest,
};
