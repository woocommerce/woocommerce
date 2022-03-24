/**
 * Internal dependencies
 */
import { hashExportArgs } from './utils';

export const isExportRequesting = ( state, selector, selectorArgs ) => {
	return Boolean(
		state.requesting[ selector ] &&
			state.requesting[ selector ][ hashExportArgs( selectorArgs ) ]
	);
};

export const getExportId = ( state, exportType, exportArgs ) => {
	return (
		state.exportIds[ exportType ] &&
		state.exportIds[ exportType ][ hashExportArgs( exportArgs ) ]
	);
};

export const getError = ( state, selector, selectorArgs ) => {
	return (
		state.errors[ selector ] &&
		state.errors[ selector ][ hashExportArgs( selectorArgs ) ]
	);
};
