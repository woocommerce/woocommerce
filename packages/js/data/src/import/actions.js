/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import TYPES from './action-types';

export function setImportStarted( activeImport ) {
	return {
		type: TYPES.SET_IMPORT_STARTED,
		activeImport,
	};
}

export function setImportPeriod( date, dateModified ) {
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

export function setSkipPrevious( skipPrevious ) {
	return {
		type: TYPES.SET_SKIP_IMPORTED,
		skipPrevious,
	};
}

export function setImportStatus( query, importStatus ) {
	return {
		type: TYPES.SET_IMPORT_STATUS,
		importStatus,
		query,
	};
}

export function setImportTotals( query, importTotals ) {
	return {
		type: TYPES.SET_IMPORT_TOTALS,
		importTotals,
		query,
	};
}

export function setImportError( query, error ) {
	return {
		type: TYPES.SET_IMPORT_ERROR,
		error,
		query,
	};
}

export function* updateImportation( path, importStarted = false ) {
	yield setImportStarted( importStarted );
	try {
		const response = yield apiFetch( { path, method: 'POST' } );
		return response;
	} catch ( error ) {
		yield setImportError( path, error );
		throw error;
	}
}
