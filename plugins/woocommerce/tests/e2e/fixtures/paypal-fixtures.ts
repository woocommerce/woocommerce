/**
 * Internal dependencies
 */
import { test as baseTest } from './fixtures';
import { ADMIN_STATE_PATH } from '../playwright.config';
import { wpCLI } from '../utils/cli';

const updatePayPalFixtureSettings = async ( shouldLoad: 'yes' | 'no' ) => {
	await wpCLI(
		`wp eval '$settings = get_option( "woocommerce_paypal_settings", array() ); if ( ! is_array( $settings ) ) { throw new RuntimeException( "woocommerce_paypal_settings must be an array." ); } $settings["enabled"] = "no"; $settings["transact_onboarding_complete"] = "no"; $settings["_should_load"] = "${ shouldLoad }"; update_option( "woocommerce_paypal_settings", $settings );'`
	);
};

const deletePayPalAccountOptions = async () => {
	await wpCLI(
		`wp eval 'delete_option( "woocommerce_paypal_transact_merchant_account_live" ); delete_option( "woocommerce_paypal_transact_merchant_account_test" ); delete_option( "woocommerce_paypal_transact_provider_account_live" ); delete_option( "woocommerce_paypal_transact_provider_account_test" );'`
	);
};

export const test = baseTest.extend( {
	page: async ( { page }, providePage ) => {
		let setupFailed = false;
		let setupError: unknown;
		const cleanupErrors: unknown[] = [];

		try {
			try {
				await updatePayPalFixtureSettings( 'yes' );
				await deletePayPalAccountOptions();
			} catch ( error ) {
				setupFailed = true;
				setupError = error;
			}

			if ( ! setupFailed ) {
				await providePage( page );
			}
		} finally {
			try {
				await updatePayPalFixtureSettings( 'no' );
			} catch ( error ) {
				cleanupErrors.push( error );
			}

			try {
				await deletePayPalAccountOptions();
			} catch ( error ) {
				cleanupErrors.push( error );
			}
		}

		if ( setupFailed && cleanupErrors.length ) {
			throw new AggregateError(
				[ setupError, ...cleanupErrors ],
				'PayPal fixture setup and cleanup failed.'
			);
		}
		if ( setupFailed ) {
			throw setupError;
		}
		if ( cleanupErrors.length ) {
			throw new AggregateError(
				cleanupErrors,
				'PayPal fixture cleanup failed.'
			);
		}
	},
	storageState: ADMIN_STATE_PATH,
} );
