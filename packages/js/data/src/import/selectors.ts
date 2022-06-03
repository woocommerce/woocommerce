/**
 * Internal dependencies
 */

import { ImportState, ImportStatusQuery, ImportTotalsQuery } from './types';

export const getImportStarted = ( state: ImportState ) => {
	const { activeImport, lastImportStartTimestamp } = state;
	return { activeImport, lastImportStartTimestamp } || {};
};

export const getFormSettings = ( state: ImportState ) => {
	const { period, skipPrevious } = state;
	return { period, skipPrevious } || {};
};

export const getImportStatus = (
	state: ImportState,
	query: ImportStatusQuery
) => {
	const stringifiedQuery = JSON.stringify( query );
	return state.importStatus[ stringifiedQuery ] || {};
};

export const getImportTotals = (
	state: ImportState,
	query: ImportTotalsQuery
) => {
	const { importTotals, lastImportStartTimestamp } = state;
	const stringifiedQuery = JSON.stringify( query );
	return (
		{
			...importTotals[ stringifiedQuery ],
			lastImportStartTimestamp,
		} || {}
	);
};

export const getImportError = (
	state: ImportState,
	query: ImportTotalsQuery | ImportStatusQuery | string
) => {
	const stringifiedQuery = JSON.stringify( query );
	return state.errors[ stringifiedQuery ] || false;
};
