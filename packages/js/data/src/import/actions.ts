/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import {
	ImportStatusQuery,
	ImportStatus,
	ImportTotals,
	ImportTotalsQuery,
} from './types';

export function setImportStarted( activeImport: boolean ) {
	return {
		type: TYPES.SET_IMPORT_STARTED,
		activeImport,
	};
}

export function setImportPeriod( date: string, dateModified: boolean ) {
	if ( ! dateModified ) {
		return {
			type: TYPES.SET_IMPORT_PERIOD,
			date,
		};
	}
	return {
		type: TYPES.SET_IMPORT_DATE,
		date,
	};
}

export function setSkipPrevious( skipPrevious: boolean ) {
	return {
		type: TYPES.SET_SKIP_IMPORTED,
		skipPrevious,
	};
}

export function setImportStatus(
	query: ImportStatusQuery,
	importStatus: ImportStatus
) {
	return {
		type: TYPES.SET_IMPORT_STATUS,
		importStatus,
		query,
	};
}

export function setImportTotals(
	query: ImportTotalsQuery,
	importTotals: ImportTotals
) {
	return {
		type: TYPES.SET_IMPORT_TOTALS,
		importTotals,
		query,
	};
}

export function setImportError(
	queryOrPath: ImportStatusQuery | ImportTotalsQuery | string,
	error: unknown
) {
	return {
		type: TYPES.SET_IMPORT_ERROR,
		error,
		query: queryOrPath,
	};
}

export function* updateImportation( path: string, importStarted = false ) {
	yield setImportStarted( importStarted );
	try {
		const response: unknown = yield apiFetch( { path, method: 'POST' } );
		return response;
	} catch ( error ) {
		yield setImportError( path, error );
		throw error;
	}
}

export type Action = ReturnType<
	| typeof setImportStarted
	| typeof setImportPeriod
	| typeof setImportStatus
	| typeof setImportTotals
	| typeof setImportError
	| typeof setSkipPrevious
>;
