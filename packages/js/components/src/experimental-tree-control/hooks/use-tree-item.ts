/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import { TreeItemProps } from '../types';
import { useExpander } from './use-expander';
import { useSelection } from './use-selection';

export function useTreeItem( {
	item,
	level,
	multiple,
	selected,
	index,
	getLabel,
	shouldItemBeExpanded,
	onSelect,
	onRemove,
	...props
}: TreeItemProps ) {
	const nextLevel = level + 1;

	const expander = useExpander( {
		item,
		shouldItemBeExpanded,
	} );

	const selection = useSelection( {
		item,
		multiple,
		selected,
		level,
		index,
		onSelect,
		onRemove,
	} );

	return {
		item,
		level: nextLevel,
		expander,
		selection,
		getLabel,
		treeItemProps: {
			...props,
		},
		headingProps: {
			style: {
				'--level': level,
			} as React.CSSProperties,
		},
		treeProps: {
			items: item.children,
			level: nextLevel,
			multiple: selection.multiple,
			selected: selection.selected,
			getItemLabel: getLabel,
			shouldItemBeExpanded,
			onSelect: selection.onSelectChildren,
			onRemove: selection.onRemoveChildren,
		},
	};
}
