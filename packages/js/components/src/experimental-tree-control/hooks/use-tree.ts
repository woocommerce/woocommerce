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
	multiple,
	selected,
	getItemLabel,
	shouldItemBeExpanded,
	isItemHighlighted,
	onSelect,
	onRemove,
	...props
}: TreeProps ) {
	return {
		level,
		items,
		treeProps: {
			...props,
		},
		treeItemProps: {
			level,
			multiple,
			selected,
			getLabel: getItemLabel,
			shouldItemBeExpanded,
			isHighlighted: isItemHighlighted,
			onSelect,
			onRemove,
		},
	};
}
