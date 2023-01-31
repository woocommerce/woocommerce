/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import { TreeItemProps } from '../types';

export function useTreeItem( { item, level, ...props }: TreeItemProps ) {
	const nextLevel = level + 1;

	return {
		item,
		level: nextLevel,
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
		},
	};
}
