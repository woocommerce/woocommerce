export const getImportStarted = ( state ) => {
	const { activeImport, lastImportStartTimestamp } = state;
	return { activeImport, lastImportStartTimestamp } || {};
};

export const getFormSettings = ( state ) => {
	const { period, skipPrevious } = state;
	return { period, skipPrevious } || {};
};

export const getImportStatus = ( state, query ) => {
	const stringifiedQuery = JSON.stringify( query );
	return state.importStatus[ stringifiedQuery ] || {};
};

export const getImportTotals = ( state, query ) => {
	const { importTotals, lastImportStartTimestamp } = state;
	const stringifiedQuery = JSON.stringify( query );
	return (
		{
			...importTotals[ stringifiedQuery ],
			lastImportStartTimestamp,
		} || {}
	);
};

export const getImportError = ( state, query ) => {
	const stringifiedQuery = JSON.stringify( query );
	return state.errors[ stringifiedQuery ] || false;
};
