/**
 * External dependencies
 */
import { useEffect, useState } from 'react';

/**
 * Internal dependencies
 */
import { TreeItemProps } from '../types';

export function useExpander( {
	isExpanded,
	item,
}: Pick< TreeItemProps, 'isExpanded' | 'item' > ) {
	const [ expanded, setExpanded ] = useState( false );

	useEffect( () => {
		if ( item.children?.length && typeof isExpanded === 'function' ) {
			setExpanded( isExpanded( item ) );
		}
	}, [ item, isExpanded ] );

	function onToggleExpand() {
		setExpanded( ( prev ) => ! prev );
	}

	return { expanded, onToggleExpand };
}
