/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import {
	DOCUMENT_LIST_QUERY_PARAM_ORDER,
	DOCUMENT_SUMMARY_QUERY_PARAM_ORDER,
	serializeDocumentsQuery,
} from './query';
import type {
	WooPaymentsDocumentsAccountResponse,
	WooPaymentsDocumentsListResponse,
	WooPaymentsDocumentsQuery,
	WooPaymentsDocumentsSummary,
	WooPaymentsVatDetails,
	WooPaymentsVatValidationResponse,
} from './types';

const ACCOUNT_PATH = '/wc-admin/settings/payments/woopayments/account';
const PAYMENTS_PATH = '/wc/v3/payments';
const DOCUMENTS_PATH = `${ PAYMENTS_PATH }/documents`;
const VAT_PATH = `${ PAYMENTS_PATH }/vat`;

const buildPathWithQuery = (
	path: string,
	query: WooPaymentsDocumentsQuery,
	paramOrder: readonly string[]
) => {
	const queryString = serializeDocumentsQuery( query, paramOrder );

	return queryString ? `${ path }?${ queryString }` : path;
};

const getWpApiSettings = () =>
	(
		globalThis as typeof globalThis & {
			wpApiSettings?: {
				root?: string;
				nonce?: string;
			};
		}
	 ).wpApiSettings;

export const getWooPaymentsDocumentsAccount =
	(): Promise< WooPaymentsDocumentsAccountResponse > =>
		apiFetch< WooPaymentsDocumentsAccountResponse >( {
			path: ACCOUNT_PATH,
			method: 'GET',
		} );

export const getWooPaymentsDocuments = (
	query: WooPaymentsDocumentsQuery = {}
): Promise< WooPaymentsDocumentsListResponse > =>
	apiFetch< WooPaymentsDocumentsListResponse >( {
		path: buildPathWithQuery(
			DOCUMENTS_PATH,
			query,
			DOCUMENT_LIST_QUERY_PARAM_ORDER
		),
		method: 'GET',
	} );

export const getWooPaymentsDocumentsSummary = (
	query: WooPaymentsDocumentsQuery = {}
): Promise< WooPaymentsDocumentsSummary > =>
	apiFetch< WooPaymentsDocumentsSummary >( {
		path: buildPathWithQuery(
			`${ DOCUMENTS_PATH }/summary`,
			query,
			DOCUMENT_SUMMARY_QUERY_PARAM_ORDER
		),
		method: 'GET',
	} );

export const buildWooPaymentsDocumentUrl = ( documentId: string ) => {
	const apiSettings = getWpApiSettings();
	const root =
		apiSettings?.root ||
		( typeof window !== 'undefined' && window.location?.origin
			? `${ window.location.origin }/wp-json/`
			: '/wp-json/' );
	const url = new URL(
		`${ root.replace(
			/\/$/,
			''
		) }${ DOCUMENTS_PATH }/${ encodeURIComponent( documentId ) }`
	);

	if ( apiSettings?.nonce ) {
		url.searchParams.set( '_wpnonce', apiSettings.nonce );
	}

	return url.toString();
};

export const validateWooPaymentsVatNumber = (
	vatNumber: string
): Promise< WooPaymentsVatValidationResponse > =>
	apiFetch< WooPaymentsVatValidationResponse >( {
		path: `${ VAT_PATH }/${ encodeURIComponent( vatNumber ) }`,
		method: 'GET',
	} );

export const saveWooPaymentsVatDetails = (
	details: WooPaymentsVatDetails
): Promise< WooPaymentsVatDetails > =>
	apiFetch< WooPaymentsVatDetails >( {
		path: VAT_PATH,
		method: 'POST',
		data: details,
	} );
