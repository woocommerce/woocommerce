/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import moment from 'moment';
import type { Reducer } from 'redux';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { ImportState } from './types';
import { Action } from './actions';

const reducer: Reducer< ImportState, Action > = (
	state = {
		activeImport: false,
		importStatus: {},
		importTotals: {},
		errors: {},
		lastImportStartTimestamp: 0,
		period: {
			date: moment().format( __( 'MM/DD/YYYY', 'woocommerce' ) ),
			label: 'all',
		},
		skipPrevious: true,
	},
	action
) => {
	switch ( action.type ) {
		case TYPES.SET_IMPORT_STARTED:
			const { activeImport } = action;
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
					label: action.date,
				},
				activeImport: false,
			};
			break;
		case TYPES.SET_IMPORT_DATE:
			state = {
				...state,
				period: {
					date: action.date,
					label: 'custom',
				},
				activeImport: false,
			};
			break;
		case TYPES.SET_SKIP_IMPORTED:
			state = {
				...state,
				skipPrevious: action.skipPrevious,
				activeImport: false,
			};
			break;
		case TYPES.SET_IMPORT_STATUS:
			const { query, importStatus } = action;
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
					[ JSON.stringify( action.query ) ]: action.importTotals,
				},
			};
			break;
		case TYPES.SET_IMPORT_ERROR:
			state = {
				...state,
				errors: {
					...state.errors,
					[ JSON.stringify( action.query ) ]: action.error,
				},
			};
			break;
	}
	return state;
};

export type State = ReturnType< typeof reducer >;
export default reducer;
