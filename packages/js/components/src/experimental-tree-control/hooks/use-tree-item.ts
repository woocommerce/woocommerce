/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import { TreeItemProps } from '../types';
import { useExpander } from './use-expander';

export function useTreeItem( {
	item,
	level,
	isExpanded,
	...props
}: TreeItemProps ) {
	const nextLevel = level + 1;

	const expander = useExpander( {
		item,
		isExpanded,
	} );

	return {
		item,
		level: nextLevel,
		expander,
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
			isItemExpanded: isExpanded,
		},
	};
}
