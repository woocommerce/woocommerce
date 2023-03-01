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
	shouldItemBeHighlighted,
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
			shouldItemBeHighlighted,
			onSelect,
			onRemove,
		},
	};
}
