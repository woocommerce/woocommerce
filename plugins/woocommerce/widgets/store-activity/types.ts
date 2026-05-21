/**
 * Store Activity Widget Types
 */

/**
 * Activity Sources - Extensibility API for Store Activity Widget
 *
 * Allows plugins to register their own activity sources via WordPress filters.
 *
 * @example
 * ```tsx
 * addFilter( 'storeActivity.sources', 'my-plugin', ( sources ) => [
 *   ...sources,
 *   { id: 'order', useActivity: useOrdersActivity },
 * ] );
 * ```
 */

type ActivityState = 'loading' | 'empty' | 'success';

/**
 * An activity source that can be registered via WordPress hooks.
 */
export interface ActivitySource {
	/**
	 * Unique identifier for the source.
	 */
	id: string;

	/**
	 * React hook that returns activity events.
	 */
	useActivity: () => ActivityHookResult;
}

/**
 * Result returned by activity hooks.
 */
export interface ActivityHookResult {
	state: ActivityState;
	events?: StoreActivityEvent[];
}

/**
 * A store activity event to be displayed in the timeline.
 */
export interface StoreActivityEvent {
	/**
	 * Unique identifier for this event.
	 */
	id: string | number;

	/**
	 * Icon to display for this event.
	 */
	icon: JSX.Element;

	/**
	 * Render function for event content.
	 * Allows plugins to render rich content with links.
	 */
	renderContent: () => React.ReactNode;

	/**
	 * Event datetime (ISO 8601). Used for sorting and grouping.
	 */
	datetime: string;
}
