/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import { TreeProps } from '../types';

export function useTree( {
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
	createValue,
	onTreeBlur,
	onCreateNew,
	shouldShowCreateButton,
	onFirstTreeItemBack,
	...props
}: TreeProps ) {
	return {
		level,
		items,
		treeProps: {
			...props,
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
