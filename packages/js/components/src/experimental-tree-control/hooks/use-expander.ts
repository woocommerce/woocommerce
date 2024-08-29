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
			typeof shouldItemBeExpanded === 'function' &&
			! isExpanded
		) {
			setExpanded( shouldItemBeExpanded( item ) );
		}
	}, [ item, shouldItemBeExpanded ] );

	function onExpand() {
		setExpanded( true );
	}

	function onCollapse() {
		setExpanded( false );
	}

	function onToggleExpand() {
		setExpanded( ( prev ) => ! prev );
	}

	return { isExpanded, onExpand, onCollapse, onToggleExpand };
}
