/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { ActivityPanelCounts } from './types';

export function getActivityPanelCountsSuccess( counts: ActivityPanelCounts ) {
	return {
		type: TYPES.GET_ACTIVITY_PANEL_COUNTS_SUCCESS as const,
		counts,
	};
}

export function getActivityPanelCountsError( error: unknown ) {
	return {
		type: TYPES.GET_ACTIVITY_PANEL_COUNTS_ERROR as const,
		error,
	};
}

export type Actions = ReturnType<
	typeof getActivityPanelCountsSuccess | typeof getActivityPanelCountsError
>;
