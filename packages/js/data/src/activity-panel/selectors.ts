/**
 * Internal dependencies
 */
import { ActivityPanelState } from './types';

/**
 * Get the Activity Panel counts from state tree.
 *
 * @param {Object} state - Reducer state
 */
export const getActivityPanelCounts = ( state: ActivityPanelState ) => {
	return state.counts;
};

/**
 * Determine if fetching the Activity Panel counts resulted in an error.
 *
 * @param {Object} state - Reducer state
 */
export const getActivityPanelCountsError = ( state: ActivityPanelState ) => {
	return state.error;
};
