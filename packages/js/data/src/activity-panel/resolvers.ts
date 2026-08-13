/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { NAMESPACE } from '../constants';
import {
	getActivityPanelCountsSuccess,
	getActivityPanelCountsError,
} from './actions';
import { ActivityPanelCounts } from './types';

/**
 * Request the Activity Panel counts (orders to fulfill, reviews to moderate,
 * low stock products) in a single request instead of one per count.
 */
export function* getActivityPanelCounts() {
	try {
		const counts: ActivityPanelCounts = yield apiFetch( {
			path: `${ NAMESPACE }/activity-panel/counts`,
		} );
		yield getActivityPanelCountsSuccess( counts );
	} catch ( error ) {
		yield getActivityPanelCountsError( error );
	}
}
