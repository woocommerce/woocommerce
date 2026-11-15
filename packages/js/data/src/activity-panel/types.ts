/**
 * Internal dependencies
 */
import { WPDataSelector, WPDataSelectors } from '../types';
import {
	getActivityPanelCounts,
	getActivityPanelCountsError,
} from './selectors';

export type ActivityPanelCounts = {
	// Null when the endpoint's underlying sub-request for this count failed.
	orders_to_fulfill_count: number | null;
	reviews_to_moderate_count: number | null;
	products_low_in_stock_count: number | null;
};

export type ActivityPanelState = {
	counts?: ActivityPanelCounts;
	error?: unknown;
};

export type ActivityPanelSelectors = {
	getActivityPanelCounts: WPDataSelector< typeof getActivityPanelCounts >;
	getActivityPanelCountsError: WPDataSelector<
		typeof getActivityPanelCountsError
	>;
} & WPDataSelectors;
