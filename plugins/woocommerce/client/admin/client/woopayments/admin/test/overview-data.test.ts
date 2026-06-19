/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import {
	confirmWooPaymentsDisputeReadinessStatementDescriptor,
	createWooPaymentsAccountSession,
	dismissWooPaymentsDisputeReadinessCard,
	getWooPaymentsDeposit,
	getWooPaymentsDeposits,
	getWooPaymentsDepositsOverview,
	getWooPaymentsDepositsSummary,
	getWooPaymentsDisputeReadiness,
	getWooPaymentsOverviewDisputes,
	getWooPaymentsOverviewShell,
	getWooPaymentsRecentDeposits,
	getWooPaymentsDepositsExportUrl,
	requestWooPaymentsDepositsExport,
} from '../overview/data';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

const mockApiFetch = apiFetch as jest.MockedFunction< typeof apiFetch >;

describe( 'WooPayments overview deposits data', () => {
	beforeEach( () => {
		mockApiFetch.mockReset();
		mockApiFetch.mockResolvedValue( {} );
	} );

	it( 'loads payout overview data from the preserved deposits overview endpoint', async () => {
		await getWooPaymentsDepositsOverview();

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/payments/deposits/overview-all',
			method: 'GET',
		} );
	} );

	it( 'loads recent payouts with the reference query names', async () => {
		await getWooPaymentsRecentDeposits( 'usd' );

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/payments/deposits?page=1&pagesize=3&sort=date&direction=desc&store_currency_is=usd',
			method: 'GET',
		} );
	} );

	it( 'preserves payout list query names instead of normalizing them to WordPress collection names', async () => {
		await getWooPaymentsDeposits( {
			page: 2,
			pagesize: 25,
			sort: 'date',
			direction: 'desc',
			store_currency_is: 'usd',
			status_is: 'paid',
		} );

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/payments/deposits?page=2&pagesize=25&sort=date&direction=desc&store_currency_is=usd&status_is=paid',
			method: 'GET',
		} );
	} );

	it( 'loads payout summaries and details from preserved endpoint names', async () => {
		await getWooPaymentsDepositsSummary( {
			store_currency_is: 'usd',
			status_is_not: 'failed',
		} );
		await getWooPaymentsDeposit( 'po_test' );

		expect( mockApiFetch ).toHaveBeenNthCalledWith( 1, {
			path: '/wc/v3/payments/deposits/summary?store_currency_is=usd&status_is_not=failed',
			method: 'GET',
		} );
		expect( mockApiFetch ).toHaveBeenNthCalledWith( 2, {
			path: '/wc/v3/payments/deposits/po_test',
			method: 'GET',
		} );
	} );

	it( 'requests payout exports and export URLs from preserved endpoint names', async () => {
		await requestWooPaymentsDepositsExport( {
			store_currency_is: 'usd',
			status_is: 'paid',
		} );
		await getWooPaymentsDepositsExportUrl( 'export_test' );

		expect( mockApiFetch ).toHaveBeenNthCalledWith( 1, {
			path: '/wc/v3/payments/deposits/download?store_currency_is=usd&status_is=paid',
			method: 'POST',
		} );
		expect( mockApiFetch ).toHaveBeenNthCalledWith( 2, {
			path: '/wc/v3/payments/deposits/download/export_test',
			method: 'GET',
		} );
	} );

	it( 'loads the overview action shell projection from the native endpoint', async () => {
		await getWooPaymentsOverviewShell();

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc-admin/settings/payments/woopayments/overview',
			method: 'GET',
		} );
	} );

	it( 'loads urgent disputes with endpoint-compatible actionable statuses', async () => {
		await getWooPaymentsOverviewDisputes();

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/payments/disputes?page=1&pagesize=50&search%5B%5D=needs_response&search%5B%5D=warning_needs_response',
			method: 'GET',
		} );
	} );

	it( 'creates embedded account sessions from the preserved endpoint', async () => {
		await createWooPaymentsAccountSession();

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/payments/accounts/session',
			method: 'GET',
		} );
	} );

	it( 'loads dispute readiness from the preserved endpoint', async () => {
		await getWooPaymentsDisputeReadiness();

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/payments/dispute-readiness',
			method: 'GET',
		} );
	} );

	it( 'dismisses dispute readiness through the preserved endpoint', async () => {
		await dismissWooPaymentsDisputeReadinessCard();

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/payments/dispute-readiness/dismiss',
			method: 'POST',
		} );
	} );

	it( 'confirms dispute readiness statement descriptors through the preserved endpoint', async () => {
		await confirmWooPaymentsDisputeReadinessStatementDescriptor();

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/payments/dispute-readiness/statement-descriptor/confirm',
			method: 'POST',
		} );
	} );
} );
