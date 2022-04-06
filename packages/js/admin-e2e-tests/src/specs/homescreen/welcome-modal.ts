/**
 * External dependencies
 */
import { withRestApi } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
 import { Login } from '../../pages/Login';
 import { OnboardingWizard } from '../../pages/OnboardingWizard';
 import { WcHomescreen } from '../../pages/WcHomescreen';
import {
	removeAllOrders,
	unhideTaskList,
	runActionScheduler,
	updateOption,
	resetWooCommerceState,
} from '../../fixtures';

/* eslint-disable @typescript-eslint/no-var-requires */
const { afterAll, beforeAll, describe, it } = require( '@jest/globals' );
/* eslint-enable @typescript-eslint/no-var-requires */

const testAdminHomescreenWelcomeModal = () => {
	describe( 'Homescreen welcome modal', () => {
		const profileWizard = new OnboardingWizard( page );
		const homeScreen = new WcHomescreen( page );
		const login = new Login( page );

		beforeAll( async () => {
			await login.login();
			await resetWooCommerceState();
			await profileWizard.navigate();
			await profileWizard.skipStoreSetup();
		} );

		afterAll( async () => {
			await withRestApi.deleteAllProducts();
			await removeAllOrders();
			await unhideTaskList( 'setup' );
			await runActionScheduler();
			await updateOption( 'woocommerce_task_list_hidden', 'no' );
			await login.logout();
		} );

		it( 'should not show welcome modal', async () => {
			await homeScreen.isDisplayed();
			await expect( homeScreen.isWelcomeModalVisible() ).resolves.toBe(
				false
			);
		} );
	} );
};

module.exports = { testAdminHomescreenWelcomeModal };
