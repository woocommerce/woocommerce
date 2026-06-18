/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import type {
	WooPaymentsCapitalLoan,
	WooPaymentsCapitalLoansResponse,
	WooPaymentsCapitalSummary,
} from './types';

const CAPITAL_PATH = '/wc/v3/payments/capital';

export const getWooPaymentsCapitalActiveLoanSummary =
	(): Promise< WooPaymentsCapitalSummary > =>
		apiFetch< WooPaymentsCapitalSummary >( {
			path: `${ CAPITAL_PATH }/active_loan_summary`,
			method: 'GET',
		} );

export const getWooPaymentsCapitalLoans = async (): Promise<
	WooPaymentsCapitalLoan[]
> => {
	const response = await apiFetch<
		WooPaymentsCapitalLoansResponse | WooPaymentsCapitalLoan[]
	>( {
		path: `${ CAPITAL_PATH }/loans`,
		method: 'GET',
	} );

	return Array.isArray( response ) ? response : response.data || [];
};
