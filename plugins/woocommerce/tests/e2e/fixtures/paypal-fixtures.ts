/**
 * Internal dependencies
 */
import { test as baseTest } from './fixtures';
import { ADMIN_STATE_PATH } from '../playwright.config';
import { wpCLI } from '../utils/cli';

const resetPayPalOnboardingState = async () => {
	await wpCLI(
		"wp option patch update woocommerce_paypal_settings transact_onboarding_complete 'no'"
	);
	await wpCLI(
		`wp eval 'delete_option( "woocommerce_paypal_transact_merchant_account_live" ); delete_option( "woocommerce_paypal_transact_merchant_account_test" ); delete_option( "woocommerce_paypal_transact_provider_account_live" ); delete_option( "woocommerce_paypal_transact_provider_account_test" );'`
	);
};

export const test = baseTest.extend( {
	page: async ( { page }, use ) => {
		await wpCLI(
			"wp option patch update woocommerce_paypal_settings _should_load 'yes'"
		);
		await resetPayPalOnboardingState();

		await use( page );

		await resetPayPalOnboardingState();
		await wpCLI(
			"wp option patch update woocommerce_paypal_settings _should_load 'no'"
		);
	},
	storageState: ADMIN_STATE_PATH,
} );
