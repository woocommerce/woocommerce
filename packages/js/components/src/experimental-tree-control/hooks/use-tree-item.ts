/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import { TreeItemProps } from '../types';

export function useTreeItem( { item, level, ...props }: TreeItemProps ) {
	const nextLevel = level + 1;
	const nextHeadingPaddingLeft = ( level - 1 ) * 28 + 12;

	return {
		item,
		level: nextLevel,
		treeItemProps: {
			...props,
		},
		headingProps: {
			style: {
				paddingLeft: nextHeadingPaddingLeft,
			},
		},
		treeProps: {
			items: item.children,
			level: nextLevel,
		},
	};
}
