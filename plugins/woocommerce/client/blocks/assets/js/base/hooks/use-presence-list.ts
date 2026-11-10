/**
 * External dependencies
 */
import { useLayoutEffect, useRef, useState } from '@wordpress/element';
import { onAnimationsFinished } from '@woocommerce/base-utils/on-animations-finished';
import {
	canStartLayoutTransition,
	isLayoutTransitionRunning,
	startLayoutChange,
} from '@woocommerce/base-utils/layout-transition';
import type { RefObject } from 'react';

/**
 * `usePresence` for a keyed list: items added after the first render are entering, and removed items stay at their
 * old index, with their last value, as exiting until the animations inside `ref` finish. With `layout`, a change
 * runs as a layout transition instead, falling back to the classes when one can't run.
 */
export const usePresenceList = < T >(
	ref: RefObject< Element >,
	items: T[],
	getKey: ( item: T ) => string,
	{ layout = false }: { layout?: boolean } = {}
) => {
	const keys = items.map( getKey );
	const keysString = keys.join( '\n' );
	const lastItems = useRef( items );
	const [ knownKeys, setKnownKeys ] = useState( keysString );
	const [ enteringKeys, setEnteringKeys ] = useState< string[] >( [] );
	const [ exiting, setExiting ] = useState< { item: T; index: number }[] >(
		[]
	);
	// The old list, shown until a layout transition applies the change.
	const [ heldItems, setHeldItems ] = useState< T[] | null >( null );

	if ( knownKeys !== keysString ) {
		setKnownKeys( keysString );
		if ( isLayoutTransitionRunning() ) {
			// Changed inside a running layout transition, which animates it.
		} else if (
			layout &&
			! enteringKeys.length &&
			! exiting.length &&
			canStartLayoutTransition( ref.current )
		) {
			setHeldItems( ( held ) => held ?? lastItems.current );
		} else {
			const lastKeys = lastItems.current.map( getKey );
			setEnteringKeys( ( current ) => [
				...current.filter( ( key ) => keys.includes( key ) ),
				...keys.filter( ( key ) => ! lastKeys.includes( key ) ),
			] );
			setExiting( ( current ) => [
				...current.filter(
					( { item } ) => ! keys.includes( getKey( item ) )
				),
				...lastItems.current.flatMap( ( item, index ) =>
					keys.includes( getKey( item ) ) ? [] : [ { item, index } ]
				),
			] );
		}
	}

	useLayoutEffect( () => {
		lastItems.current = items;
	} );

	const isHolding = heldItems !== null;
	useLayoutEffect( () => {
		if ( ! isHolding ) {
			return;
		}
		const apply = () => setHeldItems( null );
		startLayoutChange( apply, {
			element: () => ref.current,
			exit: lastItems.current.length < ( heldItems?.length ?? 0 ),
		} );
	}, [ isHolding, ref ] );

	useLayoutEffect( () => {
		if ( ! enteringKeys.length ) {
			return;
		}
		return onAnimationsFinished( ref.current, () => setEnteringKeys( [] ) );
	}, [ enteringKeys, ref ] );

	useLayoutEffect( () => {
		if ( ! exiting.length ) {
			return;
		}
		return onAnimationsFinished( ref.current, () => setExiting( [] ) );
	}, [ exiting, ref ] );

	const displayed: {
		item: T;
		className: 'is-entering' | 'is-exiting' | undefined;
	}[] = ( heldItems ?? items ).map( ( item ) => ( {
		item,
		className: enteringKeys.includes( getKey( item ) )
			? 'is-entering'
			: undefined,
	} ) );
	exiting.forEach( ( { item, index } ) =>
		displayed.splice( Math.min( index, displayed.length ), 0, {
			item,
			className: 'is-exiting',
		} )
	);
	return displayed;
};
