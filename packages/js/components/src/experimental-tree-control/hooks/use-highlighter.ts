/**
 * External dependencies
 */
import { useMemo } from 'react';

/**
 * Internal dependencies
 */
import { CheckedStatus, TreeItemProps } from '../types';

export function useHighlighter( {
	item,
	multiple,
	checkedStatus,
	shouldItemBeHighlighted,
}: Pick< TreeItemProps, 'item' | 'multiple' | 'shouldItemBeHighlighted' > & {
	checkedStatus: CheckedStatus;
} ) {
	const isHighlighted = useMemo( () => {
		if ( typeof shouldItemBeHighlighted === 'function' ) {
			if ( multiple || item.children.length === 0 ) {
				return shouldItemBeHighlighted( item );
			}
		}
		if ( ! multiple ) {
			return checkedStatus === 'checked';
		}
	}, [ item, multiple, checkedStatus, shouldItemBeHighlighted ] );

	return { isHighlighted };
}
