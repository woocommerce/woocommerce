/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { addAction, removeAction, didFilter } from '@wordpress/hooks';

/**
 * A generic hook to handle WordPress filter hooks with race condition mitigation.
 * This hook ensures that components re-render when new hooks are dynamically added
 * after the initial filter application, preventing race conditions.
 *
 * @param filterName   The name of the WordPress filter hook to watch
 * @param getterFn     Function that applies the filter and returns the filtered value
 * @param dependencies Optional dependency array for re-computation
 * @return The current filtered value
 */
export function useFilterHook< T >(
	filterName: string,
	getterFn: () => T,
	dependencies: React.DependencyList = []
): T {
	const [ value, setValue ] = useState< T >( getterFn );

	useEffect( () => {
		/**
		 * Handler for new hooks being added after the initial filter has been run,
		 * so that if any hooks are added later, they can still be applied
		 * instead of being missed due to the race condition.
		 */
		const handleHookAdded = ( hookName: string ) => {
			if (
				hookName === filterName &&
				( didFilter( filterName ) ?? 0 ) > 0
			) {
				setValue( getterFn() );
			}
		};

		const namespace = `woocommerce/woocommerce/watch_${ filterName }`;
		addAction( 'hookAdded', namespace, handleHookAdded );

		// Refresh value to catch any hooks added between initial getter and this effect
		setValue( getterFn() );

		return () => {
			removeAction( 'hookAdded', namespace );
		};
		// eslint-disable-next-line react-hooks/exhaustive-deps -- The filter name and getterFn are expected to be the same.
	}, dependencies );

	return value;
}
