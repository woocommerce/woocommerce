/**
 * Shared types for the WooCommerce-core Store Activity sources.
 *
 * These mirror, on the provider side, the contract the Store Activity widget
 * consumes through the `storeActivity.sources` filter. The boundary is a
 * runtime filter (not a typed import across workspaces), so the widget keeps
 * its own copy of the contract and both sides describe the same shape
 * independently.
 */

/**
 * Lifecycle of a source's data fetch.
 */
export type ActivityState = 'loading' | 'empty' | 'success';

/**
 * A single event contributed to the Store Activity timeline.
 */
export interface StoreActivityEvent {
	id: number;
	icon: JSX.Element;
	renderContent: () => JSX.Element;
	datetime: string;
}

/**
 * Result returned by a source's `useActivity` hook.
 */
export interface ActivityHookResult {
	state: ActivityState;
	events?: StoreActivityEvent[];
}

/**
 * A source registered through the `storeActivity.sources` filter.
 */
export interface ActivitySource {
	id: string;
	useActivity: () => ActivityHookResult;
}
