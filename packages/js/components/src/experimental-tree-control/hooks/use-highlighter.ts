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
	isHighlighted,
}: Pick< TreeItemProps, 'item' | 'multiple' | 'isHighlighted' > & {
	checkedStatus: CheckedStatus;
} ) {
	const highlighted = useMemo( () => {
		if ( typeof isHighlighted === 'function' ) {
			if ( multiple || item.children.length === 0 ) {
				return isHighlighted( item );
			}
		}
		if ( ! multiple ) {
			return checkedStatus === 'checked';
		}
	}, [ item, multiple, checkedStatus, isHighlighted ] );

	return { highlighted, isHighlighted };
}
