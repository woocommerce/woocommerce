/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import {
	closeWooPaymentsDispute,
	getWooPaymentsDispute,
	getWooPaymentsDisputeFileDetails,
	getWooPaymentsDisputes,
	getWooPaymentsTransaction,
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
		await getWooPaymentsTransactions( {
			page: 2,
			pagesize: 25,
			sort: 'date',
			direction: 'desc',
			store_currency_is: 'usd',
		} );
		await getWooPaymentsTransaction( 'txn_test' );
		await getWooPaymentsTransactionSearch( 'Ada' );
		await requestWooPaymentsTransactionsExport( {
			deposit_id: 'po_test',
		} );

		expect( mockApiFetch ).toHaveBeenNthCalledWith( 1, {
			path: '/wc/v3/payments/transactions?page=2&pagesize=25&sort=date&direction=desc&store_currency_is=usd',
			method: 'GET',
		} );
		expect( mockApiFetch ).toHaveBeenNthCalledWith( 2, {
			path: '/wc/v3/payments/transactions/txn_test',
			method: 'GET',
		} );
		expect( mockApiFetch ).toHaveBeenNthCalledWith( 3, {
			path: '/wc/v3/payments/transactions/search?search_term=Ada',
			method: 'GET',
		} );
		expect( mockApiFetch ).toHaveBeenNthCalledWith( 4, {
			path: '/wc/v3/payments/transactions/download?deposit_id=po_test',
			method: 'POST',
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
