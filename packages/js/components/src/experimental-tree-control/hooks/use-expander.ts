/**
 * External dependencies
 */
import { useEffect, useState } from 'react';

/**
 * Internal dependencies
 */
import { TreeItemProps } from '../types';

export function useExpander( {
	shouldItemBeExpanded,
	item,
}: Pick< TreeItemProps, 'shouldItemBeExpanded' | 'item' > ) {
	const [ isExpanded, setExpanded ] = useState( false );

	useEffect( () => {
		if (
			item.children?.length &&
			typeof shouldItemBeExpanded === 'function'
		) {
			setExpanded( shouldItemBeExpanded( item ) );
		}
	}, [ item, shouldItemBeExpanded ] );

	function onToggleExpand() {
		setExpanded( ( prev ) => ! prev );
	}

	return { isExpanded, onToggleExpand };
}
