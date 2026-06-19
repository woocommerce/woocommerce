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
} from './types';

const DEPOSITS_PATH = '/wc/v3/payments/deposits';

const buildPathWithQuery = (
	path: string,
	query: WooPaymentsDepositsQuery = {}
) => {
	const params = new URLSearchParams();

	Object.entries( query ).forEach( ( [ key, value ] ) => {
		if ( value === undefined || value === null || value === '' ) {
			return;
		}

		params.append( key, String( value ) );
	} );

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
