/**
 * Internal dependencies
 */
import { getSettingsPaymentsProviderRouteUrl } from '../utils';

describe( 'getSettingsPaymentsProviderRouteUrl', () => {
	beforeEach( () => {
		window.wcSettings = {
			...window.wcSettings,
			adminUrl: 'https://example.com/wp-admin',
		};
	} );

	it( 'builds provider route URLs without query parameters', () => {
		expect(
			getSettingsPaymentsProviderRouteUrl( '/woopayments/payouts' )
		).toBe(
			'https://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Fpayouts'
		);
	} );

	it( 'builds express checkout provider route URLs', () => {
		expect(
			getSettingsPaymentsProviderRouteUrl(
				'/woopayments/settings/express-checkout/payment_request'
			)
		).toBe(
			'https://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Fsettings%2Fexpress-checkout%2Fpayment_request'
		);
	} );

	it( 'keeps route query parameters outside the encoded path', () => {
		expect(
			getSettingsPaymentsProviderRouteUrl(
				'/woopayments/settings/express-checkout/payment_request?from=settings-payments'
			)
		).toBe(
			'https://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Fsettings%2Fexpress-checkout%2Fpayment_request&from=settings-payments'
		);
	} );
} );
