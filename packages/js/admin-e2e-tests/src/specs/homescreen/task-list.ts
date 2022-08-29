/**
 * External dependencies
 */
import { afterAll, beforeAll, describe, it } from '@jest/globals';
import { takeScreenshotFor } from '@woocommerce/e2e-environment';

/**
 * Internal dependencies
 */
import { Login } from '../../pages/Login';
import { OnboardingWizard } from '../../pages/OnboardingWizard';
import { WcHomescreen } from '../../pages/WcHomescreen';
import { TaskTitles } from '../../constants/taskTitles';
import { HelpMenu } from '../../elements/HelpMenu';
import { WcSettings } from '../../pages/WcSettings';
import { resetWooCommerceState, unhideTaskList } from '../../fixtures';

export const testAdminHomescreenTasklist = () => {
	describe( 'Homescreen task list', () => {
		const profileWizard = new OnboardingWizard( page );
		const homeScreen = new WcHomescreen( page );
		const helpMenu = new HelpMenu( page );
		const settings = new WcSettings( page );
		const login = new Login( page );

		beforeAll( async () => {
			await login.login();
			await resetWooCommerceState();

			// This makes this test more isolated, by always navigating to the
			// profile wizard and skipping, this behaves the same as if the
			// profile wizard had not been run yet and the user is redirected
			// to it when trying to go to wc-admin.
			await profileWizard.navigate();
			await profileWizard.skipStoreSetup();

			await homeScreen.isDisplayed();
			await homeScreen.possiblyDismissWelcomeModal();
			await takeScreenshotFor( 'WooCommerce Admin Home Screen' );
		} );

		afterAll( async () => {
			await unhideTaskList( 'setup' );
			await login.logout();
		} );

		it( 'should show 6 or more tasks on the home screen', async () => {
			const tasks = await homeScreen.getTaskList();
			expect( tasks.length ).toBeGreaterThanOrEqual( 6 );
			expect( tasks ).toContain( TaskTitles.storeDetails );
			expect( tasks ).toContain( TaskTitles.addProducts );
			expect( tasks ).toContain( TaskTitles.taxSetup );
			expect( tasks ).toContain( TaskTitles.personalizeStore );
		} );

		it( 'should be able to hide the task list', async () => {
			await homeScreen.hideTaskList();
			expect( await homeScreen.isTaskListDisplayed() ).toBe( false );
		} );

		it( 'should be able to show the task list again through the help menu', async () => {
			await settings.navigate();
			await helpMenu.openHelpMenu();
			await helpMenu.enableTaskList();
			// redirects to homescreen
			await homeScreen.isDisplayed();
			await expect( homeScreen.isTaskListDisplayed() ).resolves.toBe(
				true
			);
		} );
	} );
};
