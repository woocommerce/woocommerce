/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import {
	buildWooPaymentsDocumentUrl,
	getWooPaymentsDocuments,
	getWooPaymentsDocumentsAccount,
	getWooPaymentsDocumentsSummary,
	saveWooPaymentsVatDetails,
	validateWooPaymentsVatNumber,
} from '../documents/data';
import { dataViewsViewToDocumentsQuery } from '../documents/query';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

const mockApiFetch = apiFetch as jest.MockedFunction< typeof apiFetch >;

describe( 'WooPayments Documents data helpers', () => {
	beforeEach( () => {
		mockApiFetch.mockReset();
		mockApiFetch.mockResolvedValue( {} );
		( global as unknown as { wpApiSettings?: unknown } ).wpApiSettings = {
			root: 'https://site.test/wp-json/',
			nonce: 'rest-nonce',
		};
	} );

	it( 'loads account document metadata from the native account summary', async () => {
		await getWooPaymentsDocumentsAccount();

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc-admin/settings/payments/woopayments/account',
			method: 'GET',
		} );
	} );

	it( 'preserves Documents list endpoint paths and query names', async () => {
		await getWooPaymentsDocuments( {
			page: 2,
			pagesize: 25,
			sort: 'date',
			direction: 'desc',
			match: 'all',
			date_before: '2026-06-18',
			date_after: '2026-06-01',
			date_between: [ '2026-06-01', '2026-06-18' ],
			type_is: 'vat_invoice',
			type_is_not: 'unknown',
			ignored: 'drop-me',
		} );

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/payments/documents?page=2&pagesize=25&sort=date&direction=desc&match=all&date_before=2026-06-18&date_after=2026-06-01&date_between%5B%5D=2026-06-01&date_between%5B%5D=2026-06-18&type_is=vat_invoice&type_is_not=unknown',
			method: 'GET',
		} );
	} );

	it( 'preserves Documents summary filter-only endpoint paths', async () => {
		await getWooPaymentsDocumentsSummary( {
			page: 9,
			pagesize: 100,
			sort: 'date',
			direction: 'asc',
			match: 'any',
			type_is: 'vat_invoice',
			date_after: '2026-06-01',
		} );

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/payments/documents/summary?match=any&date_after=2026-06-01&type_is=vat_invoice',
			method: 'GET',
		} );
	} );

	it( 'maps DataViews filters to preserved REST query params', () => {
		expect(
			dataViewsViewToDocumentsQuery( {
				type: 'table',
				filters: [
					{
						field: 'date',
						operator: 'before',
						value: '2026-06-18',
					},
					{
						field: 'date',
						operator: 'after',
						value: '2026-06-01',
					},
					{
						field: 'type',
						operator: 'isNot',
						value: 'vat_invoice',
					},
				],
			} )
		).toEqual(
			expect.objectContaining( {
				date_before: '2026-06-18',
				date_after: '2026-06-01',
				type_is_not: 'vat_invoice',
			} )
		);
	} );

	it( 'builds reference-compatible document download URLs', () => {
		expect( buildWooPaymentsDocumentUrl( 'vat_invoice_123' ) ).toBe(
			'https://site.test/wp-json/wc/v3/payments/documents/vat_invoice_123?_wpnonce=rest-nonce'
		);
	} );

	it( 'preserves VAT validation and save endpoint paths', async () => {
		await validateWooPaymentsVatNumber( 'DE 123456789' );
		await saveWooPaymentsVatDetails( {
			vat_number: 'DE 123456789',
			name: 'Ada Bakery',
			address: '1 Market Street',
		} );

		expect( mockApiFetch ).toHaveBeenNthCalledWith( 1, {
			path: '/wc/v3/payments/vat/DE%20123456789',
			method: 'GET',
		} );
		expect( mockApiFetch ).toHaveBeenNthCalledWith( 2, {
			path: '/wc/v3/payments/vat',
			method: 'POST',
			data: {
				vat_number: 'DE 123456789',
				name: 'Ada Bakery',
				address: '1 Market Street',
			},
		} );
	} );
} );
