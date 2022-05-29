/**
 * Internal dependencies
 */
import { OptionsState } from './types';

/**
 * Get option from state tree.
 *
 * @param {Object} state - Reducer state
 * @param {Array}  name  - Option name
 */
export const getOption = ( state: OptionsState, name: string ) => {
	return state[ name ];
};

/**
 * Determine if an options request resulted in an error.
 *
 * @param {Object} state - Reducer state
 * @param {string} name  - Option name
 */
export const getOptionsRequestingError = (
	state: OptionsState,
	name: string
) => {
	return state.requestingErrors[ name ] || false;
};

/**
 * Determine if options are being updated.
 *
 * @param {Object} state - Reducer state
 */
export const isOptionsUpdating = ( state: OptionsState ) => {
	return state.isUpdating || false;
};

/**
 * Determine if an options update resulted in an error.
 *
 * @param {Object} state - Reducer state
 */
export const getOptionsUpdatingError = ( state: OptionsState ) => {
	return state.updatingError || false;
};
