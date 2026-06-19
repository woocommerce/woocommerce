/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import {
	getWooPaymentsReportsBalanceSummary,
	getWooPaymentsReportsFees,
	getWooPaymentsReportsFeesExportUrl,
	getWooPaymentsReportsFeesSummary,
	requestWooPaymentsReportsFeesExport,
} from '../reports/data';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

const mockApiFetch = apiFetch as jest.MockedFunction< typeof apiFetch >;

describe( 'WooPayments Reports data helpers', () => {
	beforeEach( () => {
		mockApiFetch.mockReset();
		mockApiFetch.mockResolvedValue( {} );
	} );

	it( 'loads Balance summaries from the Reports balance endpoint', async () => {
		await getWooPaymentsReportsBalanceSummary( {
			date_start: '2026-06-01T00:00:00Z',
			date_end: '2026-06-19T23:59:59Z',
			currency: 'USD',
		} );

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/payments/reports/balance?date_start=2026-06-01T00%3A00%3A00Z&date_end=2026-06-19T23%3A59%3A59Z&currency=usd',
			method: 'GET',
		} );
	} );

	it( 'loads Fees rows with deterministic list query params', async () => {
		await getWooPaymentsReportsFees( {
			page: 2,
			per_page: 50,
			sort: 'date',
			direction: 'desc',
			date_between: [ '2026-06-01', '2026-06-19' ],
			payment_method_type: 'card',
			type: [ 'charge', 'refund' ],
			search: [ 'txn_123' ],
			user_timezone: '+03:00',
			user_email: 'merchant@example.com',
			locale: 'en_US',
		} );

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/payments/reports/fees?page=2&per_page=50&sort=date&direction=desc&date_between%5B%5D=2026-06-01&date_between%5B%5D=2026-06-19&payment_method_type=card&type%5B%5D=charge&type%5B%5D=refund&search%5B%5D=txn_123&user_timezone=%2B03%3A00',
			method: 'GET',
		} );
	} );

	it( 'loads Fees summaries without pagination, sort, or export-only params', async () => {
		await getWooPaymentsReportsFeesSummary( {
			page: 3,
			per_page: 100,
			sort: 'date',
			direction: 'asc',
			date_after: '2026-06-01',
			type: [ 'charge' ],
			user_timezone: '+03:00',
			user_email: 'merchant@example.com',
			locale: 'en_US',
		} );

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/payments/reports/fees/summary?date_after=2026-06-01&type%5B%5D=charge&user_timezone=%2B03%3A00',
			method: 'GET',
		} );
	} );

	it( 'starts Fees exports with export-only params scoped to the POST URL', async () => {
		await requestWooPaymentsReportsFeesExport( {
			date_before: '2026-06-19',
			search: [ 'po_123' ],
			user_timezone: '+03:00',
			user_email: 'merchant@example.com',
			locale: 'en_US',
		} );

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/payments/reports/fees/download?date_before=2026-06-19&search%5B%5D=po_123&user_timezone=%2B03%3A00&user_email=merchant%40example.com&locale=en_US',
			method: 'POST',
		} );
	} );

	it( 'polls Fees export download URLs by encoded export id', async () => {
		await getWooPaymentsReportsFeesExportUrl( 'export id/123' );

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/payments/reports/fees/download/export%20id%2F123',
			method: 'GET',
		} );
	} );
} );
