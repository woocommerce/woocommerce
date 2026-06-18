/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import type {
	WooPaymentsDispute,
	WooPaymentsDisputeFile,
	WooPaymentsListResponse,
	WooPaymentsMoneyMovementQuery,
	WooPaymentsTransaction,
} from './types';
import { buildPathWithQuery } from './utils';

const PAYMENTS_PATH = '/wc/v3/payments';

export const getWooPaymentsTransactions = (
	query: WooPaymentsMoneyMovementQuery = {}
): Promise< WooPaymentsListResponse< WooPaymentsTransaction > > =>
	apiFetch< WooPaymentsListResponse< WooPaymentsTransaction > >( {
		path: buildPathWithQuery( `${ PAYMENTS_PATH }/transactions`, query ),
		method: 'GET',
	} );

export const getWooPaymentsTransactionsSummary = (
	query: WooPaymentsMoneyMovementQuery = {}
): Promise< Record< string, unknown > > =>
	apiFetch< Record< string, unknown > >( {
		path: buildPathWithQuery(
			`${ PAYMENTS_PATH }/transactions/summary`,
			query
		),
		method: 'GET',
	} );

export const getWooPaymentsTransaction = (
	transactionId: string
): Promise< WooPaymentsTransaction > =>
	apiFetch< WooPaymentsTransaction >( {
		path: `${ PAYMENTS_PATH }/transactions/${ encodeURIComponent(
			transactionId
		) }`,
		method: 'GET',
	} );

export const getWooPaymentsTransactionSearch = (
	searchTerm: string
): Promise< Array< { label: string } > > =>
	apiFetch< Array< { label: string } > >( {
		path: buildPathWithQuery( `${ PAYMENTS_PATH }/transactions/search`, {
			search_term: searchTerm,
		} ),
		method: 'GET',
	} );

export const requestWooPaymentsTransactionsExport = (
	query: WooPaymentsMoneyMovementQuery = {}
): Promise< Record< string, unknown > > =>
	apiFetch< Record< string, unknown > >( {
		path: buildPathWithQuery(
			`${ PAYMENTS_PATH }/transactions/download`,
			query
		),
		method: 'POST',
	} );

export const getWooPaymentsDisputes = (
	query: WooPaymentsMoneyMovementQuery = {}
): Promise< WooPaymentsListResponse< WooPaymentsDispute > > =>
	apiFetch< WooPaymentsListResponse< WooPaymentsDispute > >( {
		path: buildPathWithQuery( `${ PAYMENTS_PATH }/disputes`, query ),
		method: 'GET',
	} );

export const getWooPaymentsDisputesSummary = (
	query: WooPaymentsMoneyMovementQuery = {}
): Promise< Record< string, unknown > > =>
	apiFetch< Record< string, unknown > >( {
		path: buildPathWithQuery(
			`${ PAYMENTS_PATH }/disputes/summary`,
			query
		),
		method: 'GET',
	} );

export const getWooPaymentsDispute = (
	disputeId: string
): Promise< WooPaymentsDispute > =>
	apiFetch< WooPaymentsDispute >( {
		path: `${ PAYMENTS_PATH }/disputes/${ encodeURIComponent(
			disputeId
		) }`,
		method: 'GET',
	} );

export const requestWooPaymentsDisputesExport = (
	query: WooPaymentsMoneyMovementQuery = {}
): Promise< Record< string, unknown > > =>
	apiFetch< Record< string, unknown > >( {
		path: buildPathWithQuery(
			`${ PAYMENTS_PATH }/disputes/download`,
			query
		),
		method: 'POST',
	} );

export const updateWooPaymentsDispute = (
	disputeId: string,
	data: {
		evidence: Record< string, unknown >;
		submit: boolean;
		metadata: Record< string, unknown >;
	}
): Promise< WooPaymentsDispute > =>
	apiFetch< WooPaymentsDispute >( {
		path: `${ PAYMENTS_PATH }/disputes/${ encodeURIComponent(
			disputeId
		) }`,
		method: 'POST',
		data,
	} );

export const uploadWooPaymentsDisputeFile = (
	body: FormData
): Promise< WooPaymentsDisputeFile > =>
	apiFetch< WooPaymentsDisputeFile >( {
		path: `${ PAYMENTS_PATH }/file`,
		method: 'POST',
		body,
	} );

export const getWooPaymentsDisputeFileDetails = (
	fileId: string
): Promise< WooPaymentsDisputeFile > =>
	apiFetch< WooPaymentsDisputeFile >( {
		path: `${ PAYMENTS_PATH }/file/${ encodeURIComponent(
			fileId
		) }/details`,
		method: 'GET',
	} );

export const closeWooPaymentsDispute = (
	disputeId: string
): Promise< WooPaymentsDispute > =>
	apiFetch< WooPaymentsDispute >( {
		path: `${ PAYMENTS_PATH }/disputes/${ encodeURIComponent(
			disputeId
		) }/close`,
		method: 'POST',
	} );
