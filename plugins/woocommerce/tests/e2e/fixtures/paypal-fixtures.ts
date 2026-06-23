/**
 * Internal dependencies
 */
import { test as baseTest } from './fixtures';
import { ADMIN_STATE_PATH } from '../playwright.config';
import { wpCLI } from '../utils/cli';

export const test = baseTest.extend( {
	page: async ( { page }, use ) => {
		await wpCLI(
			"wp option patch update woocommerce_paypal_settings _should_load 'yes'"
		);

		await use( page );

		await wpCLI(
			"wp option patch update woocommerce_paypal_settings _should_load 'no'"
		);
	},
	storageState: ADMIN_STATE_PATH,
} );
