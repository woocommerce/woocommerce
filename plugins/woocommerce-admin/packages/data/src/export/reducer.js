/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { hashExportArgs } from './utils';

const exportReducer = (
	state = {
		errors: {},
		requesting: {},
		exportMeta: {},
		exportIds: {},
	},
	{
		error,
		exportArgs,
		exportId,
		exportType,
		isRequesting,
		selector,
		selectorArgs,
		type,
	}
) => {
	switch ( type ) {
		case TYPES.SET_IS_REQUESTING:
			return {
				...state,
				requesting: {
					...state.requesting,
					[ selector ]: {
						...state.requesting[ selector ],
						[ hashExportArgs( selectorArgs ) ]: isRequesting,
					},
				},
			};
		case TYPES.SET_EXPORT_ID:
			return {
				...state,
				exportMeta: {
					...state.exportMeta,
					[ exportId ]: {
						exportType,
						exportArgs,
					},
				},
				exportIds: {
					...state.exportIds,
					[ exportType ]: {
						...state.exportIds[ exportType ],
						[ hashExportArgs( {
							type: exportType,
							args: exportArgs,
						} ) ]: exportId,
					},
				},
			};
		case TYPES.SET_ERROR:
			return {
				...state,
				errors: {
					...state.errors,
					[ selector ]: {
						...state.errors[ selector ],
						[ hashExportArgs( selectorArgs ) ]: error,
					},
				},
			};
		default:
			return state;
	}
};

export default exportReducer;
