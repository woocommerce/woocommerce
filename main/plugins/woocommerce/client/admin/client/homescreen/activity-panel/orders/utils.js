/**
 * External dependencies
 */
import { settingsStore } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { DEFAULT_ACTIONABLE_STATUSES } from '../../../analytics/settings/config';

export function getOrderStatuses( select ) {
	const { getSetting: getMutableSetting } = select( settingsStore );
	const {
		woocommerce_actionable_order_statuses:
			orderStatuses = DEFAULT_ACTIONABLE_STATUSES,
	} = getMutableSetting( 'wc_admin', 'wcAdminSettings', {} );
	return orderStatuses;
}

// Still used by the Stock panel's own low-stock list fetch — the count
// itself now comes from activityPanelStore's getActivityPanelCounts().
export const getLowStockCountQuery = {
	status: 'publish',
};
