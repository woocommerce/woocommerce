/**
 * External dependencies
 */
import { addFilter, removeFilter } from '@wordpress/hooks';

// Store registered email filters so they can be removed on cleanup
const emailFiltersRegistry = new Set< string >();

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
 * Removes all filters that were registered via addFilterForEmail.
 */
export function clearEmailFilters(): void {
	for ( const key of emailFiltersRegistry ) {
		const [ hookName, namespace ] = key.split( '||' );
		removeFilter( hookName, namespace );
		emailFiltersRegistry.delete( key );
	}
}
