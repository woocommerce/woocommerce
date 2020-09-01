/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import moment from 'moment';

/**
 * Internal dependencies
 */
import TYPES from './action-types';

const reducer = (
	state = {
		activeImport: false,
		importStatus: {},
		importTotals: {},
		errors: {},
		lastImportStartTimestamp: 0,
		period: {
			date: moment().format( __( 'MM/DD/YYYY', 'woocommerce-admin' ) ),
			label: 'all',
		},
		skipPrevious: true,
	},
	{
		type,
		query,
		importStatus,
		importTotals,
		activeImport,
		date,
		error,
		skipPrevious,
	}
) => {
	switch ( type ) {
		case TYPES.SET_IMPORT_STARTED:
			state = {
				...state,
				activeImport,
				lastImportStartTimestamp: activeImport
					? Date.now()
					: state.lastImportStartTimestamp,
			};
			break;
		case TYPES.SET_IMPORT_PERIOD:
			state = {
				...state,
				period: {
					...state.period,
					label: date,
				},
				activeImport: false,
			};
			break;
		case TYPES.SET_IMPORT_DATE:
			state = {
				...state,
				period: {
					date,
					label: 'custom',
				},
				activeImport: false,
			};
			break;
		case TYPES.SET_SKIP_IMPORTED:
			state = {
				...state,
				skipPrevious,
				activeImport: false,
			};
			break;
		case TYPES.SET_IMPORT_STATUS:
			state = {
				...state,
				importStatus: {
					...state.importStatus,
					[ JSON.stringify( query ) ]: importStatus,
				},
				errors: {
					...state.errors,
					[ JSON.stringify( query ) ]: false,
				},
			};
			break;
		case TYPES.SET_IMPORT_TOTALS:
			state = {
				...state,
				importTotals: {
					...state.importTotals,
					[ JSON.stringify( query ) ]: importTotals,
				},
			};
			break;
		case TYPES.SET_IMPORT_ERROR:
			state = {
				...state,
				errors: {
					...state.errors,
					[ JSON.stringify( query ) ]: error,
				},
			};
			break;
	}
	return state;
};

export default reducer;
