/**
 * External dependencies
 */
import {
	addFilter,
	removeFilter,
	addAction,
	removeAction,
} from '@wordpress/hooks';

// Store registered email filters and actions so they can be removed on cleanup
const emailFiltersRegistry = new Set< string >();
const emailActionsRegistry = new Set< string >();

function makeKey( hookName: string, namespace: string ): string {
	return `${ hookName }||${ namespace }`;
}

/**
 * Adds a filter and stores the pair (hookName, namespace) for later cleanup.
 * Mirrors addFilter API.
 */
export function addFilterForEmail<
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	TCallback extends ( ...args: any[] ) => any
>(
	hookName: string,
	namespace: string,
	callback: TCallback,
	priority?: number
): void {
	addFilter( hookName, namespace, callback, priority );
	emailFiltersRegistry.add( makeKey( hookName, namespace ) );
}

/**
 * Adds an action and stores the pair (hookName, namespace) for later cleanup.
 * Mirrors addAction API.
 */
export function addActionForEmail<
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	TCallback extends ( ...args: any[] ) => any
>(
	hookName: string,
	namespace: string,
	callback: TCallback,
	priority?: number
): void {
	addAction( hookName, namespace, callback, priority );
	emailActionsRegistry.add( makeKey( hookName, namespace ) );
}

/**
 * Removes all filters that were registered via addFilterForEmail.
 */
export function clearEmailFilters(): void {
	for ( const key of emailFiltersRegistry ) {
		const [ hookName, namespace ] = key.split( '||' );
		removeFilter( hookName, namespace );
		emailFiltersRegistry.delete( key );
	}
}

/**
 * Removes all actions that were registered via addActionForEmail.
 */
export function clearEmailActions(): void {
	for ( const key of emailActionsRegistry ) {
		const [ hookName, namespace ] = key.split( '||' );
		removeAction( hookName, namespace );
		emailActionsRegistry.delete( key );
	}
}

/**
 * Removes all filters and actions that were registered via addFilterForEmail and addActionForEmail.
 */
export function clearAllEmailHooks(): void {
	clearEmailFilters();
	clearEmailActions();
}
