/**
 * External dependencies
 */
import React from 'react';
import { useInstanceId } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { TreeItemProps } from '../types';
import { useExpander } from './use-expander';
import { useHighlighter } from './use-highlighter';
import { useKeyboard } from './use-keyboard';
import { useSelection } from './use-selection';

export function useTreeItem( {
	item,
	level,
	multiple,
	shouldNotRecursivelySelect,
	selected,
	index,
	getLabel,
	shouldItemBeExpanded,
	shouldItemBeHighlighted,
	onSelect,
	onRemove,
	isExpanded,
	onCreateNew,
	shouldShowCreateButton,
	onLastItemLoop,
	onFirstItemLoop,
	onTreeBlur,
	onEscape,
	highlightedIndex,
	isHighlighted,
	onExpand,
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
		shouldNotRecursivelySelect,
	} );

	const highlighter = useHighlighter( {
		item,
		checkedStatus: selection.checkedStatus,
		multiple,
		shouldItemBeHighlighted,
	} );

	const subTreeId = `experimental-woocommerce-tree__group-${ useInstanceId(
		useTreeItem
	) }`;

	const { onKeyDown } = useKeyboard( {
		...expander,
		onLastItemLoop,
		onFirstItemLoop,
		item,
	} );

	return {
		item,
		level: nextLevel,
		expander,
		selection,
		highlighter,
		getLabel,
		treeItemProps: {
			...props,
			id:
				'woocommerce-experimental-tree-control__menu-item-' +
				item.index,
			role: 'option',
		},
		headingProps: {
			role: 'treeitem',
			'aria-selected': selection.checkedStatus !== 'unchecked',
			'aria-expanded': item.children.length
				? item.data.isExpanded
				: undefined,
			'aria-owns':
				item.children.length && item.data.isExpanded
					? subTreeId
					: undefined,
			style: {
				'--level': level,
			} as React.CSSProperties,
			onKeyDown,
		},
		treeProps: {
			id: subTreeId,
			items: item.children,
			level: nextLevel,
			multiple: selection.multiple,
			selected: selection.selected,
			role: 'group',
			'aria-label': item.data.label,
			getItemLabel: getLabel,
			shouldItemBeExpanded,
			shouldItemBeHighlighted,
			shouldNotRecursivelySelect,
			onSelect: selection.onSelectChildren,
			onRemove: selection.onRemoveChildren,
		},
	};
}
