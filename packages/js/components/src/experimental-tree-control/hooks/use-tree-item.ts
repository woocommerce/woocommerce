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
	shouldItemBeExpanded,
	...props
}: TreeItemProps ) {
	const nextLevel = level + 1;

	const expander = useExpander( {
		item,
		shouldItemBeExpanded,
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
			shouldItemBeExpanded,
		},
	};
}
