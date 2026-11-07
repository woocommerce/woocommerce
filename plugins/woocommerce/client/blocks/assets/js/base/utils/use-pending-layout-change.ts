/**
 * External dependencies
 */
import { flushSync, useCallback, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { startLayoutTransition } from './layout-transition';

/**
 * Applies a React state `update` as a layout transition (see `startLayoutTransition`), or straight away where
 * that isn't available. Returns whether it transitioned.
 */
export const startLayoutChange = (
	update: () => void,
	options?: Parameters< typeof startLayoutTransition >[ 1 ]
): boolean => {
	if ( startLayoutTransition( () => flushSync( update ), options ) ) {
		return true;
	}
	update();
	return false;
};

/**
 * For a control whose change runs as a layout transition, which applies it a frame or two after the click:
 * `withLayoutChange( onChange )` holds the new value in `pending` until then, so render `pending ?? value`.
 */
export const usePendingLayoutChange = < T >( sections?: string ) => {
	const [ pending, setPending ] = useState< T | null >( null );
	const withLayoutChange = useCallback(
		( onChange: ( value: T ) => void ) => ( value: T ) => {
			const apply = () => {
				setPending( null );
				onChange( value );
			};
			if ( startLayoutChange( apply, { sections } ) ) {
				setPending( value );
			}
		},
		[ sections ]
	);
	return [ pending, withLayoutChange ] as const;
};
