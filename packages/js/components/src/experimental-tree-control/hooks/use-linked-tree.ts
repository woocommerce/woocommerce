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

const shouldItemBeExpanded = (
	item: LinkedTree,
	createValue: string | undefined
): boolean => {
	if ( ! createValue || ! item.children?.length ) return false;
	return item.children.some( ( child ) => {
		if ( new RegExp( createValue || '', 'ig' ).test( child.data.label ) ) {
			return true;
		}
		return shouldItemBeExpanded( child, createValue );
	} );
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
		linkedTree.data.isExpanded =
			linkedTree.children.length > 0 ? false : true;
		return linkedTree;
	} );
}

export function getLinkedTree( items: Item[] ): LinkedTree[] {
	return findChildren(
		items.map( ( i, index ) => ( { ...i, index, isExpanded: false } ) ),
		undefined,
		{}
	);
}

export function expandNodeNumber(
	tree: LinkedTree[],
	number: number,
	value: boolean
): LinkedTree[] {
	return tree.map( ( node ) => {
		return {
			...node,
			children: node.children
				? expandNodeNumber( node.children, number, value )
				: node.children,
			data: {
				...node.data,
				isExpanded:
					node.data.index === number ? value : node.data.isExpanded,
			},
			...( node.parent
				? {
						parent: {
							...node.parent,
							data: {
								...node.parent.data,
								isExpanded:
									node.parent.data.index === number
										? value
										: node.parent.data.isExpanded,
							},
						},
				  }
				: {} ),
		};
	} );
}

export function getVisibleNodeIndex(
	tree: LinkedTree[],
	highlightedIndex: number,
	direction: 'up' | 'down'
): number | undefined {
	if ( direction === 'down' ) {
		for ( const node of tree ) {
			if ( ! node.parent || node.parent.data.isExpanded ) {
				if ( node.data.index >= highlightedIndex ) {
					return node.data.index;
				}
				const visibleNodeIndex = getVisibleNodeIndex(
					node.children,
					highlightedIndex,
					direction
				);
				if ( visibleNodeIndex !== undefined ) {
					return visibleNodeIndex;
				}
			}
		}
	} else {
		for ( let i = tree.length - 1; i >= 0; i-- ) {
			const node = tree[ i ];
			if ( ! node.parent || node.parent.data.isExpanded ) {
				const visibleNodeIndex = getVisibleNodeIndex(
					node.children,
					highlightedIndex,
					direction
				);
				if ( visibleNodeIndex !== undefined ) {
					return visibleNodeIndex;
				}
				if ( node.data.index <= highlightedIndex ) {
					return node.data.index;
				}
			}
		}
	}

	return undefined;
}
