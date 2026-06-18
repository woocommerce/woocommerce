/**
 * Internal dependencies
 */
import { getSettingsPaymentsProviderRouteUrl } from '../overview/utils';

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

	it( 'keeps route query parameters outside the encoded path', () => {
		expect(
			getSettingsPaymentsProviderRouteUrl(
				'/woopayments/transactions/details?id=txn_test'
			)
		).toBe(
			'https://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Ftransactions%2Fdetails&id=txn_test'
		);
	} );
} );
