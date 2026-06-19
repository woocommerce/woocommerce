/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import type {
	WooPaymentsDeposit,
	WooPaymentsDepositsListResponse,
	WooPaymentsDepositsOverview,
	WooPaymentsDepositsQuery,
	WooPaymentsDepositsSummary,
	WooPaymentsOverviewDisputesResponse,
	WooPaymentsOverviewShell,
	WooPaymentsAccountSession,
	WooPaymentsDisputeReadinessPayload,
} from './types';

const DEPOSITS_PATH = '/wc/v3/payments/deposits';
const DISPUTES_PATH = '/wc/v3/payments/disputes';
const ACCOUNTS_SESSION_PATH = '/wc/v3/payments/accounts/session';
const DISPUTE_READINESS_PATH = '/wc/v3/payments/dispute-readiness';
const OVERVIEW_SHELL_PATH = '/wc-admin/settings/payments/woopayments/overview';
const DISPUTE_AWAITING_RESPONSE_STATUSES = [
	'needs_response',
	'warning_needs_response',
];

const buildPathWithQuery = (
	path: string,
	query: WooPaymentsDepositsQuery | Record< string, unknown > = {}
) => {
	const params = new URLSearchParams();

	Object.entries( query as Record< string, unknown > ).forEach(
		( [ key, value ] ) => {
			if ( value === undefined || value === null || value === '' ) {
				return;
			}

			if ( Array.isArray( value ) ) {
				value.forEach( ( item ) => {
					if ( item !== undefined && item !== null && item !== '' ) {
						params.append( `${ key }[]`, String( item ) );
					}
				} );

				return;
			}

			params.append( key, String( value ) );
		}
	);

	const queryString = params.toString();

	return queryString ? `${ path }?${ queryString }` : path;
};

export const getWooPaymentsDepositsOverview =
	async (): Promise< WooPaymentsDepositsOverview > =>
		apiFetch< WooPaymentsDepositsOverview >( {
			path: `${ DEPOSITS_PATH }/overview-all`,
			method: 'GET',
		} );

export const getWooPaymentsDeposits = async (
	query: WooPaymentsDepositsQuery = {}
): Promise< WooPaymentsDepositsListResponse > =>
	apiFetch< WooPaymentsDepositsListResponse >( {
		path: buildPathWithQuery( DEPOSITS_PATH, query ),
		method: 'GET',
	} );

export const getWooPaymentsRecentDeposits = (
	currency: string
): Promise< WooPaymentsDepositsListResponse > =>
	getWooPaymentsDeposits( {
		page: 1,
		pagesize: 3,
		sort: 'date',
		direction: 'desc',
		store_currency_is: currency,
	} );

export const getWooPaymentsDepositsSummary = async (
	query: WooPaymentsDepositsQuery = {}
): Promise< WooPaymentsDepositsSummary > =>
	apiFetch< WooPaymentsDepositsSummary >( {
		path: buildPathWithQuery( `${ DEPOSITS_PATH }/summary`, query ),
		method: 'GET',
	} );

export const requestWooPaymentsDepositsExport = async (
	query: WooPaymentsDepositsQuery = {}
): Promise< Record< string, unknown > > =>
	apiFetch< Record< string, unknown > >( {
		path: buildPathWithQuery( `${ DEPOSITS_PATH }/download`, query ),
		method: 'POST',
	} );

export const getWooPaymentsDepositsExportUrl = async (
	exportId: string
): Promise< Record< string, unknown > > =>
	apiFetch< Record< string, unknown > >( {
		path: `${ DEPOSITS_PATH }/download/${ encodeURIComponent( exportId ) }`,
		method: 'GET',
	} );

export const getWooPaymentsDeposit = async (
	depositId: string
): Promise< WooPaymentsDeposit > =>
	apiFetch< WooPaymentsDeposit >( {
		path: `${ DEPOSITS_PATH }/${ encodeURIComponent( depositId ) }`,
		method: 'GET',
	} );

export const submitWooPaymentsInstantDeposit = async (
	currency: string
): Promise< WooPaymentsDeposit > =>
	apiFetch< WooPaymentsDeposit >( {
		path: DEPOSITS_PATH,
		method: 'POST',
		data: {
			type: 'instant',
			currency,
		},
	} );

export const getWooPaymentsOverviewShell =
	async (): Promise< WooPaymentsOverviewShell > =>
		apiFetch< WooPaymentsOverviewShell >( {
			path: OVERVIEW_SHELL_PATH,
			method: 'GET',
		} );

export const getWooPaymentsOverviewDisputes =
	async (): Promise< WooPaymentsOverviewDisputesResponse > =>
		apiFetch< WooPaymentsOverviewDisputesResponse >( {
			path: buildPathWithQuery( DISPUTES_PATH, {
				page: 1,
				pagesize: 50,
				search: DISPUTE_AWAITING_RESPONSE_STATUSES,
			} ),
			method: 'GET',
		} );

export const createWooPaymentsAccountSession =
	async (): Promise< WooPaymentsAccountSession > =>
		apiFetch< WooPaymentsAccountSession >( {
			path: ACCOUNTS_SESSION_PATH,
			method: 'GET',
		} );

export const getWooPaymentsDisputeReadiness =
	async (): Promise< WooPaymentsDisputeReadinessPayload > =>
		apiFetch< WooPaymentsDisputeReadinessPayload >( {
			path: DISPUTE_READINESS_PATH,
			method: 'GET',
		} );

export const dismissWooPaymentsDisputeReadinessCard =
	async (): Promise< WooPaymentsDisputeReadinessPayload > =>
		apiFetch< WooPaymentsDisputeReadinessPayload >( {
			path: `${ DISPUTE_READINESS_PATH }/dismiss`,
			method: 'POST',
		} );

export const confirmWooPaymentsDisputeReadinessStatementDescriptor =
	async (): Promise< WooPaymentsDisputeReadinessPayload > =>
		apiFetch< WooPaymentsDisputeReadinessPayload >( {
			path: `${ DISPUTE_READINESS_PATH }/statement-descriptor/confirm`,
			method: 'POST',
		} );
