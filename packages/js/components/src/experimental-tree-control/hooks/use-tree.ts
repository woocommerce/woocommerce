/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import { TreeProps } from '../types';

export function useTree( {
	ref,
	items,
	level = 1,
	role = 'tree',
	multiple,
	selected,
	getItemLabel,
	shouldItemBeExpanded,
	shouldItemBeHighlighted,
	onSelect,
	onRemove,
	shouldNotRecursivelySelect,
	listToFindOriginalIndex,
	getMenuProps,
	getItemProps,
	...props
}: TreeProps ) {
	return {
		level,
		items,
		treeProps: {
			...props,
			ref,
			role,
		},
		treeItemProps: {
			level,
			multiple,
			selected,
			getLabel: getItemLabel,
			shouldItemBeExpanded,
			shouldItemBeHighlighted,
			shouldNotRecursivelySelect,
			onSelect,
			onRemove,
		},
	};
}
