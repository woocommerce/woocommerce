/**
 * External dependencies
 */
import { useMemo } from 'react';

/**
 * Internal dependencies
 */
import { Item, LinkedTree } from '../types';

type MemoItems = {
	[ value: Item[ 'value' ] ]: LinkedTree;
};

function findChildren(
	items: Item[],
	parent?: Item[ 'parent' ],
	memo: MemoItems = {}
): LinkedTree[] {
	const children: Item[] = [];
	const others: Item[] = [];

	items.forEach( ( item ) => {
		if ( item.parent === parent ) {
			children.push( item );
		} else {
			others.push( item );
		}
		memo[ item.value ] = {
			parent: undefined,
			data: item,
			children: [],
		};
	} );

	return children.map( ( child ) => {
		const linkedTree = memo[ child.value ];
		linkedTree.parent = child.parent ? memo[ child.parent ] : undefined;
		linkedTree.children = findChildren( others, child.value, memo );
		return linkedTree;
	} );
}

export function useLinkedTree( items: Item[] ): LinkedTree[] {
	const linkedTree = useMemo( () => {
		return findChildren( items, undefined, {} );
	}, [ items ] );

	return linkedTree;
}
