/**
 * External dependencies
 */
import { useMemo } from 'react';

/**
 * Internal dependencies
 */
import { Item, LinkedTree } from '../types';

type MemoItems = {
	[ value: Item[ 'id' ] ]: LinkedTree;
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
		memo[ item.id ] = {
			parent: undefined,
			data: item,
			children: [],
		};
	} );

	return children.map( ( child ) => {
		const linkedTree = memo[ child.id ];
		linkedTree.parent = child.parent ? memo[ child.parent ] : undefined;
		linkedTree.children = findChildren( others, child.id, memo );
		return linkedTree;
	} );
}

export function useLinkedTree( items: Item[] ): LinkedTree[] {
	const linkedTree = useMemo( () => {
		return findChildren( items, undefined, {} );
	}, [ items ] );

	return linkedTree;
}
