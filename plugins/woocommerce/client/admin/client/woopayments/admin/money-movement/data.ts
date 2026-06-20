/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import type {
	WooPaymentsAuthorization,
	WooPaymentsAuthorizationActionResponse,
	WooPaymentsAuthorizationQuery,
	WooPaymentsAuthorizationsSummary,
	WooPaymentsDispute,
	WooPaymentsDisputeFile,
	WooPaymentsFraudOutcomeQuery,
	WooPaymentsListResponse,
	WooPaymentsMoneyMovementQuery,
	WooPaymentsCharge,
	WooPaymentsPaymentIntent,
	WooPaymentsRefundRequest,
	WooPaymentsRefundResponse,
	WooPaymentsReaderChargeSummaryResponse,
	WooPaymentsTransaction,
	WooPaymentsTimelineResponse,
} from './types';
import { buildPathWithQuery } from './utils';
import { serializeWooPaymentsAuthorizationsQuery } from './query';

const PAYMENTS_PATH = '/wc/v3/payments';

const buildAuthorizationsPath = (
	path: string,
	query: WooPaymentsAuthorizationQuery = {}
) => {
	const queryString = serializeWooPaymentsAuthorizationsQuery( query );

	return queryString ? `${ path }?${ queryString }` : path;
};

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

export const getWooPaymentsCharge = (
	chargeId: string
): Promise< WooPaymentsCharge > =>
	apiFetch< WooPaymentsCharge >( {
		path: `${ PAYMENTS_PATH }/charges/${ encodeURIComponent( chargeId ) }`,
		method: 'GET',
	} );

export const getWooPaymentsPaymentIntent = (
	paymentIntentId: string
): Promise< WooPaymentsPaymentIntent > =>
	apiFetch< WooPaymentsPaymentIntent >( {
		path: `${ PAYMENTS_PATH }/payment_intents/${ encodeURIComponent(
			paymentIntentId
		) }`,
		method: 'GET',
	} );

export const getWooPaymentsTimeline = (
	timelineId: string
): Promise< WooPaymentsTimelineResponse > =>
	apiFetch< WooPaymentsTimelineResponse >( {
		path: `${ PAYMENTS_PATH }/timeline/${ encodeURIComponent(
			timelineId
		) }`,
		method: 'GET',
	} );

export const getWooPaymentsReaderChargeSummary = (
	transactionId: string,
	options: { signal?: AbortSignal } = {}
): Promise< WooPaymentsReaderChargeSummaryResponse > =>
	apiFetch< WooPaymentsReaderChargeSummaryResponse >( {
		path: `${ PAYMENTS_PATH }/readers/charges/${ encodeURIComponent(
			transactionId
		) }`,
		method: 'GET',
		...( options.signal ? { signal: options.signal } : {} ),
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

export const getWooPaymentsTransactionsExportUrl = (
	exportId: string
): Promise< Record< string, unknown > > =>
	apiFetch< Record< string, unknown > >( {
		path: `${ PAYMENTS_PATH }/transactions/download/${ encodeURIComponent(
			exportId
		) }`,
		method: 'GET',
	} );

export const getWooPaymentsAuthorizations = (
	query: WooPaymentsAuthorizationQuery = {}
): Promise< WooPaymentsListResponse< WooPaymentsAuthorization > > =>
	apiFetch< WooPaymentsListResponse< WooPaymentsAuthorization > >( {
		path: buildAuthorizationsPath(
			`${ PAYMENTS_PATH }/authorizations`,
			query
		),
		method: 'GET',
	} );

export const getWooPaymentsAuthorizationsSummary = (
	query: WooPaymentsAuthorizationQuery = {}
): Promise< WooPaymentsAuthorizationsSummary > =>
	apiFetch< WooPaymentsAuthorizationsSummary >( {
		path: buildAuthorizationsPath(
			`${ PAYMENTS_PATH }/authorizations/summary`,
			query
		),
		method: 'GET',
	} );

export const getWooPaymentsAuthorization = (
	paymentIntentId: string
): Promise< WooPaymentsAuthorization > =>
	apiFetch< WooPaymentsAuthorization >( {
		path: `${ PAYMENTS_PATH }/authorizations/${ encodeURIComponent(
			paymentIntentId
		) }`,
		method: 'GET',
	} );

export const captureWooPaymentsAuthorization = (
	orderId: number | string,
	paymentIntentId: string
): Promise< WooPaymentsAuthorizationActionResponse > =>
	apiFetch< WooPaymentsAuthorizationActionResponse >( {
		path: `${ PAYMENTS_PATH }/orders/${ encodeURIComponent(
			String( orderId )
		) }/capture_authorization`,
		method: 'POST',
		data: {
			payment_intent_id: paymentIntentId,
		},
	} );

export const cancelWooPaymentsAuthorization = (
	orderId: number | string,
	paymentIntentId: string
): Promise< WooPaymentsAuthorizationActionResponse > =>
	apiFetch< WooPaymentsAuthorizationActionResponse >( {
		path: `${ PAYMENTS_PATH }/orders/${ encodeURIComponent(
			String( orderId )
		) }/cancel_authorization`,
		method: 'POST',
		data: {
			payment_intent_id: paymentIntentId,
		},
	} );

export const refundWooPaymentsCharge = ( {
	chargeId,
	amount,
	reason,
	orderId,
}: WooPaymentsRefundRequest ): Promise< WooPaymentsRefundResponse > =>
	apiFetch< WooPaymentsRefundResponse >( {
		path: `${ PAYMENTS_PATH }/refund`,
		method: 'POST',
		data: {
			charge_id: chargeId,
			amount,
			reason,
			order_id: orderId,
		},
	} );

export const getWooPaymentsFraudOutcomeTransactions = (
	query: WooPaymentsFraudOutcomeQuery = {}
): Promise< WooPaymentsListResponse< WooPaymentsTransaction > > =>
	apiFetch< WooPaymentsListResponse< WooPaymentsTransaction > >( {
		path: buildPathWithQuery(
			`${ PAYMENTS_PATH }/transactions/fraud-outcomes`,
			query
		),
		method: 'GET',
	} );

export const getWooPaymentsFraudOutcomeTransactionsSummary = (
	query: WooPaymentsFraudOutcomeQuery = {}
): Promise< Record< string, unknown > > =>
	apiFetch< Record< string, unknown > >( {
		path: buildPathWithQuery(
			`${ PAYMENTS_PATH }/transactions/fraud-outcomes/summary`,
			query
		),
		method: 'GET',
	} );

export const getWooPaymentsFraudOutcomeTransactionSearch = (
	searchTerm: string
): Promise< Array< { label: string } > > =>
	apiFetch< Array< { label: string } > >( {
		path: buildPathWithQuery(
			`${ PAYMENTS_PATH }/transactions/fraud-outcomes/search`,
			{
				search_term: searchTerm,
			}
		),
		method: 'GET',
	} );

export const getWooPaymentsFraudOutcomeTransactionsExport = (
	query: WooPaymentsFraudOutcomeQuery = {}
): Promise< WooPaymentsListResponse< WooPaymentsTransaction > > =>
	apiFetch< WooPaymentsListResponse< WooPaymentsTransaction > >( {
		path: buildPathWithQuery(
			`${ PAYMENTS_PATH }/transactions/fraud-outcomes/download`,
			query
		),
		method: 'GET',
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

export const getWooPaymentsDisputesExportUrl = (
	exportId: string
): Promise< Record< string, unknown > > =>
	apiFetch< Record< string, unknown > >( {
		path: `${ PAYMENTS_PATH }/disputes/download/${ encodeURIComponent(
			exportId
		) }`,
		method: 'GET',
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
