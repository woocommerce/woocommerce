/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import { TreeProps } from '../types';

export function useTree( { ref, items, level = 1, ...props }: TreeProps ) {
	return {
		level,
		parent,
		treeProps: {
			...props,
		},
		treeItemProps: {
			items,
			level,
		},
	};
}
