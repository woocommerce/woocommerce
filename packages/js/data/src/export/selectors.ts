/**
 * Internal dependencies
 */
import { hashExportArgs } from './utils';
import { ExportState, SelectorArgs, ExportArgs } from './types';

export const isExportRequesting = (
	state: ExportState,
	selector: string,
	selectorArgs: SelectorArgs
) => {
	return Boolean(
		state.requesting[ selector ] &&
			state.requesting[ selector ][ hashExportArgs( selectorArgs ) ]
	);
};

export const getExportId = (
	state: ExportState,
	exportType: string,
	exportArgs: ExportArgs
) => {
	return (
		state.exportIds[ exportType ] &&
		state.exportIds[ exportType ][ hashExportArgs( exportArgs ) ]
	);
};

export const getError = (
	state: ExportState,
	selector: string,
	selectorArgs: SelectorArgs
) => {
	return (
		state.errors[ selector ] &&
		state.errors[ selector ][ hashExportArgs( selectorArgs ) ]
	);
};
