/**
 * Internal dependencies
 */
import { WPDataSelector, WPDataSelectors } from '../types';
import {
	getActivityPanelCounts,
	getActivityPanelCountsError,
} from './selectors';

export type ActivityPanelCounts = {
	orders_to_fulfill_count: number;
	reviews_to_moderate_count: number;
	products_low_in_stock_count: number;
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
