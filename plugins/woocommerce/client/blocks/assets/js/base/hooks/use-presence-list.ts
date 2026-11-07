/**
 * External dependencies
 */
import { useLayoutEffect, useRef, useState } from '@wordpress/element';
import { onAnimationsFinished } from '@woocommerce/base-utils/on-animations-finished';
import type { RefObject } from 'react';

/**
 * `usePresence` for a keyed list: items added after the first render are entering, and removed items stay at their
 * old index, with their last value, as exiting until the animations inside `ref` finish.
 */
export const usePresenceList = < T >(
	ref: RefObject< Element >,
	items: T[],
	getKey: ( item: T ) => string
) => {
	const keys = items.map( getKey );
	const keysString = keys.join( '\n' );
	const lastItems = useRef( items );
	const [ knownKeys, setKnownKeys ] = useState( keysString );
	const [ enteringKeys, setEnteringKeys ] = useState< string[] >( [] );
	const [ exiting, setExiting ] = useState< { item: T; index: number }[] >(
		[]
	);

	if ( knownKeys !== keysString ) {
		setKnownKeys( keysString );
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

	useLayoutEffect( () => {
		lastItems.current = items;
	} );

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
	}[] = items.map( ( item ) => ( {
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
