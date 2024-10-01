/**
 * Internal dependencies
 */
const {
	merchant,
	completeOnboardingWizard,
	withRestApi,
	addShippingZoneAndMethod,
	IS_RETEST_MODE,
} = require( '@woocommerce/e2e-utils' );

/**
 * External dependencies
 */
const config = require( 'config' );
import deprecated from '@wordpress/deprecated';
const { it, describe } = require( '@jest/globals' );

const shippingZoneNameUS = config.get( 'addresses.customer.shipping.country' );

const runOnboardingFlowTest = () => {
	describe( 'Store owner can go through store Onboarding', () => {
		beforeAll( async () => {
			await merchant.login();
		} );

		if ( IS_RETEST_MODE ) {
			it( 'can reset onboarding to default settings', async () => {
				await withRestApi.resetOnboarding();
			} );

			it( 'can reset shipping zones to default settings', async () => {
				await withRestApi.deleteAllShippingZones();
			} );

			it( 'can reset shipping classes', async () => {
				await withRestApi.deleteAllShippingClasses();
			} );

			it( 'can reset to default settings', async () => {
				await withRestApi.resetSettingsGroupToDefault( 'general' );
				await withRestApi.resetSettingsGroupToDefault( 'products' );
				await withRestApi.resetSettingsGroupToDefault( 'tax' );
			} );
		}

		it( 'can start and complete onboarding when visiting the site for the first time.', async () => {
			deprecated( 'runOnboardingFlowTest', {
				alternative:
					'@woocommerce/admin-e2e-tests `testAdminOnboardingWizard()`',
			} );
			await completeOnboardingWizard();
		} );
	} );
};

const runTaskListTest = () => {
	describe( 'Store owner can go through setup Task List', () => {
		beforeAll( async () => {
			await merchant.login();
		} );

		it( 'can setup shipping', async () => {
			deprecated( 'runTaskListTest', {
				alternative:
					'@woocommerce/admin-e2e-tests `testAdminHomescreenTasklist()`',
			} );
			await page.evaluate( () => {
				document
					.querySelector( '.woocommerce-list__item-title' )
					.scrollIntoView();
			} );
			// Query for all tasks on the list
			const taskListItems = await page.$$(
				'.woocommerce-list__item-title'
			);
			expect( taskListItems.length ).toBeInRange( 5, 6 );

			// Work around for https://github.com/woocommerce/woocommerce-admin/issues/6761
			if ( taskListItems.length === 6 ) {
				// Click on "Get your products shipped" task to move to the next step
				const [ setupTaskListItem ] = await page.$x(
					'//div[contains(text(),"Get your products shipped")]'
				);
				await setupTaskListItem.click();

				// Wait for "Proceed" button to become active
				await page.waitForSelector(
					'button.is-primary:not(:disabled)'
				);
				await page.waitFor( 3000 );

				// Click on "Proceed" button to save shipping settings
				await page.click( 'button.is-primary' );
				await page.waitFor( 3000 );
			} else {
				await merchant.openNewShipping();
				await addShippingZoneAndMethod( shippingZoneNameUS );
			}
		} );
	} );
};

// eslint-disable-next-line jest/no-export
module.exports = {
	runOnboardingFlowTest,
	runTaskListTest,
};
