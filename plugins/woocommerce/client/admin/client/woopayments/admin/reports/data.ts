/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import {
	serializeReportsBalanceQuery,
	serializeReportsFeesExportQuery,
	serializeReportsFeesListQuery,
	serializeReportsFeesSummaryQuery,
} from './query';
import type {
	ReportsBalanceQuery,
	ReportsBalanceSummary,
	ReportsFee,
	ReportsFeesQuery,
	ReportsFeesSummary,
} from './types';

const REPORTS_PATH = '/wc/v3/payments/reports';

const buildPathWithQuery = ( path: string, queryString: string ) =>
	queryString ? `${ path }?${ queryString }` : path;

export const getWooPaymentsReportsBalanceSummary = (
	query: ReportsBalanceQuery = {}
): Promise< ReportsBalanceSummary > =>
	apiFetch< ReportsBalanceSummary >( {
		path: buildPathWithQuery(
			`${ REPORTS_PATH }/balance`,
			serializeReportsBalanceQuery( query )
		),
		method: 'GET',
	} );

export const getWooPaymentsReportsFees = (
	query: ReportsFeesQuery = {}
): Promise< ReportsFee[] > =>
	apiFetch< ReportsFee[] >( {
		path: buildPathWithQuery(
			`${ REPORTS_PATH }/fees`,
			serializeReportsFeesListQuery( query )
		),
		method: 'GET',
	} );

export const getWooPaymentsReportsFeesSummary = (
	query: ReportsFeesQuery = {}
): Promise< ReportsFeesSummary > =>
	apiFetch< ReportsFeesSummary >( {
		path: buildPathWithQuery(
			`${ REPORTS_PATH }/fees/summary`,
			serializeReportsFeesSummaryQuery( query )
		),
		method: 'GET',
	} );

export const requestWooPaymentsReportsFeesExport = (
	query: ReportsFeesQuery = {}
): Promise< Record< string, unknown > > =>
	apiFetch< Record< string, unknown > >( {
		path: buildPathWithQuery(
			`${ REPORTS_PATH }/fees/download`,
			serializeReportsFeesExportQuery( query )
		),
		method: 'POST',
	} );

export const getWooPaymentsReportsFeesExportUrl = (
	exportId: string
): Promise< Record< string, unknown > > =>
	apiFetch< Record< string, unknown > >( {
		path: `${ REPORTS_PATH }/fees/download/${ encodeURIComponent(
			exportId
		) }`,
		method: 'GET',
	} );
