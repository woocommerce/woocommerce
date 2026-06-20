/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import * as moneyMovementData from '../money-movement/data';
import {
	closeWooPaymentsDispute,
	getWooPaymentsDispute,
	getWooPaymentsDisputeFileDetails,
	getWooPaymentsDisputes,
	getWooPaymentsDisputesExportUrl,
	getWooPaymentsCharge,
	getWooPaymentsPaymentIntent,
	getWooPaymentsTransaction,
	getWooPaymentsTimeline,
	getWooPaymentsTransactionsExportUrl,
	getWooPaymentsFraudOutcomeTransactions,
	getWooPaymentsFraudOutcomeTransactionsSummary,
	getWooPaymentsFraudOutcomeTransactionsExport,
	getWooPaymentsFraudOutcomeTransactionSearch,
	getWooPaymentsReaderChargeSummary,
	getWooPaymentsTransactionSearch,
	getWooPaymentsTransactions,
	requestWooPaymentsDisputesExport,
	requestWooPaymentsTransactionsExport,
	updateWooPaymentsDispute,
	uploadWooPaymentsDisputeFile,
} from '../money-movement/data';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

const mockApiFetch = apiFetch as jest.MockedFunction< typeof apiFetch >;

describe( 'WooPayments money movement data helpers', () => {
	beforeEach( () => {
		mockApiFetch.mockReset();
		mockApiFetch.mockResolvedValue( {} );
	} );

	it( 'preserves transactions endpoint paths and query names', async () => {
		const readerSummaryAbortController = new AbortController();

		await getWooPaymentsTransactions( {
			page: 2,
			pagesize: 25,
			sort: 'date',
			direction: 'desc',
			store_currency_is: 'usd',
		} );
		await getWooPaymentsTransaction( 'txn_test' );
		await getWooPaymentsCharge( 'ch_test' );
		await getWooPaymentsPaymentIntent( 'pi_test' );
		await getWooPaymentsTimeline( 'pi_test' );
		await getWooPaymentsReaderChargeSummary( 'txn_reader_fee_123', {
			signal: readerSummaryAbortController.signal,
		} );
		await getWooPaymentsTransactionSearch( 'Ada' );
		await requestWooPaymentsTransactionsExport( {
			deposit_id: 'po_test',
		} );
		await getWooPaymentsTransactionsExportUrl( 'export_test' );

		expect( mockApiFetch ).toHaveBeenNthCalledWith( 1, {
			path: '/wc/v3/payments/transactions?page=2&pagesize=25&sort=date&direction=desc&store_currency_is=usd',
			method: 'GET',
		} );
		expect( mockApiFetch ).toHaveBeenNthCalledWith( 2, {
			path: '/wc/v3/payments/transactions/txn_test',
			method: 'GET',
		} );
		expect( mockApiFetch ).toHaveBeenNthCalledWith( 3, {
			path: '/wc/v3/payments/charges/ch_test',
			method: 'GET',
		} );
		expect( mockApiFetch ).toHaveBeenNthCalledWith( 4, {
			path: '/wc/v3/payments/payment_intents/pi_test',
			method: 'GET',
		} );
		expect( mockApiFetch ).toHaveBeenNthCalledWith( 5, {
			path: '/wc/v3/payments/timeline/pi_test',
			method: 'GET',
		} );
		expect( mockApiFetch ).toHaveBeenNthCalledWith( 6, {
			path: '/wc/v3/payments/readers/charges/txn_reader_fee_123',
			method: 'GET',
			signal: readerSummaryAbortController.signal,
		} );
		expect( mockApiFetch ).toHaveBeenNthCalledWith( 7, {
			path: '/wc/v3/payments/transactions/search?search_term=Ada',
			method: 'GET',
		} );
		expect( mockApiFetch ).toHaveBeenNthCalledWith( 8, {
			path: '/wc/v3/payments/transactions/download?deposit_id=po_test',
			method: 'POST',
		} );
		expect( mockApiFetch ).toHaveBeenNthCalledWith( 9, {
			path: '/wc/v3/payments/transactions/download/export_test',
			method: 'GET',
		} );
	} );

	it( 'preserves authorizations endpoint paths and action routes', async () => {
		const dataHelpers = moneyMovementData as unknown as Record<
			string,
			( ...args: unknown[] ) => Promise< unknown >
		>;

		expect( typeof dataHelpers.getWooPaymentsAuthorizations ).toBe(
			'function'
		);
		expect( typeof dataHelpers.getWooPaymentsAuthorization ).toBe(
			'function'
		);
		expect( typeof dataHelpers.getWooPaymentsAuthorizationsSummary ).toBe(
			'function'
		);
		expect( typeof dataHelpers.captureWooPaymentsAuthorization ).toBe(
			'function'
		);
		expect( typeof dataHelpers.cancelWooPaymentsAuthorization ).toBe(
			'function'
		);

		await dataHelpers.getWooPaymentsAuthorizations( {
			page: 2,
			pagesize: 25,
			sort: 'capture_by',
			direction: 'desc',
			search: 'Ada',
			loan_id_is: 'loan_test',
			deposit_id: 'po_test',
			store_currency_is: 'usd',
		} );
		await dataHelpers.getWooPaymentsAuthorization( 'pi_test' );
		await dataHelpers.getWooPaymentsAuthorizationsSummary( {
			sort: 'capture_by',
			direction: 'asc',
			type_is: 'charge',
		} );
		await dataHelpers.captureWooPaymentsAuthorization( 123, 'pi_test' );
		await dataHelpers.cancelWooPaymentsAuthorization( 123, 'pi_test' );

		expect( mockApiFetch ).toHaveBeenNthCalledWith( 1, {
			path: '/wc/v3/payments/authorizations?page=2&pagesize=25&sort=created&direction=desc&search=Ada',
			method: 'GET',
		} );
		expect( mockApiFetch ).toHaveBeenNthCalledWith( 2, {
			path: '/wc/v3/payments/authorizations/pi_test',
			method: 'GET',
		} );
		expect( mockApiFetch ).toHaveBeenNthCalledWith( 3, {
			path: '/wc/v3/payments/authorizations/summary?sort=created&direction=asc',
			method: 'GET',
		} );
		expect( mockApiFetch ).toHaveBeenNthCalledWith( 4, {
			path: '/wc/v3/payments/orders/123/capture_authorization',
			method: 'POST',
			data: {
				payment_intent_id: 'pi_test',
			},
		} );
		expect( mockApiFetch ).toHaveBeenNthCalledWith( 5, {
			path: '/wc/v3/payments/orders/123/cancel_authorization',
			method: 'POST',
			data: {
				payment_intent_id: 'pi_test',
			},
		} );
	} );

	it( 'preserves fraud outcome endpoint paths and query names', async () => {
		await getWooPaymentsFraudOutcomeTransactions( {
			status: 'block',
			page: 2,
			pagesize: 25,
			sort: 'date',
			direction: 'desc',
			search: 'Ada',
		} );
		await getWooPaymentsFraudOutcomeTransactionsSummary( {
			status: 'block',
		} );
		await getWooPaymentsFraudOutcomeTransactionSearch( 'Ada' );
		await getWooPaymentsFraudOutcomeTransactionsExport( {
			status: 'block',
		} );

		expect( mockApiFetch ).toHaveBeenNthCalledWith( 1, {
			path: '/wc/v3/payments/transactions/fraud-outcomes?status=block&page=2&pagesize=25&sort=date&direction=desc&search=Ada',
			method: 'GET',
		} );
		expect( mockApiFetch ).toHaveBeenNthCalledWith( 2, {
			path: '/wc/v3/payments/transactions/fraud-outcomes/summary?status=block',
			method: 'GET',
		} );
		expect( mockApiFetch ).toHaveBeenNthCalledWith( 3, {
			path: '/wc/v3/payments/transactions/fraud-outcomes/search?search_term=Ada',
			method: 'GET',
		} );
		expect( mockApiFetch ).toHaveBeenNthCalledWith( 4, {
			path: '/wc/v3/payments/transactions/fraud-outcomes/download?status=block',
			method: 'GET',
		} );
	} );

	it( 'preserves disputes endpoint paths and query names', async () => {
		await getWooPaymentsDisputes( {
			page: 1,
			pagesize: 25,
			status_is: 'needs_response',
		} );
		await getWooPaymentsDispute( 'dp_test' );
		await updateWooPaymentsDispute( 'dp_test', {
			evidence: { customer_name: 'Ada' },
			submit: true,
			metadata: { order_id: 123 },
		} );
		await closeWooPaymentsDispute( 'dp_test' );
		await requestWooPaymentsDisputesExport( {
			status_is: 'needs_response',
		} );
		await getWooPaymentsDisputesExportUrl( 'export_test' );

		expect( mockApiFetch ).toHaveBeenNthCalledWith( 1, {
			path: '/wc/v3/payments/disputes?page=1&pagesize=25&status_is=needs_response',
			method: 'GET',
		} );
		expect( mockApiFetch ).toHaveBeenNthCalledWith( 2, {
			path: '/wc/v3/payments/disputes/dp_test',
			method: 'GET',
		} );
		expect( mockApiFetch ).toHaveBeenNthCalledWith( 3, {
			path: '/wc/v3/payments/disputes/dp_test',
			method: 'POST',
			data: {
				evidence: { customer_name: 'Ada' },
				submit: true,
				metadata: { order_id: 123 },
			},
		} );
		expect( mockApiFetch ).toHaveBeenNthCalledWith( 4, {
			path: '/wc/v3/payments/disputes/dp_test/close',
			method: 'POST',
		} );
		expect( mockApiFetch ).toHaveBeenNthCalledWith( 5, {
			path: '/wc/v3/payments/disputes/download?status_is=needs_response',
			method: 'POST',
		} );
		expect( mockApiFetch ).toHaveBeenNthCalledWith( 6, {
			path: '/wc/v3/payments/disputes/download/export_test',
			method: 'GET',
		} );
	} );

	it( 'posts dispute evidence file uploads as form data', async () => {
		const formData = new FormData();
		formData.append(
			'file',
			new File( [ 'receipt' ], 'receipt.pdf', {
				type: 'application/pdf',
			} )
		);
		formData.append( 'purpose', 'dispute_evidence' );
		mockApiFetch.mockResolvedValueOnce( {
			id: 'file_test',
			filename: 'receipt.pdf',
			size: 7,
		} );

		const response = await uploadWooPaymentsDisputeFile( formData );

		expect( response ).toMatchObject( {
			id: 'file_test',
			filename: 'receipt.pdf',
			size: 7,
		} );
		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/payments/file',
			method: 'POST',
			body: formData,
		} );
	} );

	it( 'fetches dispute evidence file details by file ID', async () => {
		mockApiFetch.mockResolvedValueOnce( {
			id: 'file_test',
			filename: 'receipt.pdf',
			size: 7,
		} );

		const response = await getWooPaymentsDisputeFileDetails( 'file_test' );

		expect( response ).toMatchObject( {
			id: 'file_test',
			filename: 'receipt.pdf',
			size: 7,
		} );
		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/payments/file/file_test/details',
			method: 'GET',
		} );
	} );
} );
