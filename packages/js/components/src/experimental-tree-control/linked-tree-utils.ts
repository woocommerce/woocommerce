/**
 * Internal dependencies
 */
import { AugmentedItem, Item, LinkedTree } from './types';

type MemoItems = {
	[ value: AugmentedItem[ 'value' ] ]: LinkedTree;
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
	items: AugmentedItem[],
	memo: MemoItems = {},
	parent?: AugmentedItem[ 'parent' ],
	createValue?: string | undefined
): LinkedTree[] {
	const children: AugmentedItem[] = [];
	const others: AugmentedItem[] = [];

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
		linkedTree.children = findChildren(
			others,
			memo,
			child.value,
			createValue
		);
		linkedTree.data.isExpanded =
			linkedTree.children.length === 0
				? true
				: shouldItemBeExpanded( linkedTree, createValue );
		return linkedTree;
	} );
}

function populateIndexes(
	linkedTree: LinkedTree[],
	startCount = 0
): LinkedTree[] {
	let count = startCount;

	function populate( tree: LinkedTree[] ): number {
		for ( const node of tree ) {
			node.index = count;
			count++;
			if ( node.children ) {
				count = populate( node.children );
			}
		}
		return count;
	}

	populate( linkedTree );
	return linkedTree;
}

// creates a linked tree from an array of Items
export function createLinkedTree(
	items: Item[],
	value: string | undefined
): LinkedTree[] {
	const augmentedItems = items.map( ( i ) => ( {
		...i,
		isExpanded: false,
	} ) );
	return populateIndexes(
		findChildren( augmentedItems, {}, undefined, value )
	);
}

// Toggles the expanded state of a node in a linked tree
export function toggleNode(
	tree: LinkedTree[],
	number: number,
	value: boolean
): LinkedTree[] {
	return tree.map( ( node ) => {
		return {
			...node,
			children: node.children
				? toggleNode( node.children, number, value )
				: node.children,
			data: {
				...node.data,
				isExpanded:
					node.index === number ? value : node.data.isExpanded,
			},
			...( node.parent
				? {
						parent: {
							...node.parent,
							data: {
								...node.parent.data,
								isExpanded:
									node.parent.index === number
										? value
										: node.parent.data.isExpanded,
							},
						},
				  }
				: {} ),
		};
	} );
}

// Gets the index of the next/previous visible node in the linked tree
export function getVisibleNodeIndex(
	tree: LinkedTree[],
	highlightedIndex: number,
	direction: 'up' | 'down'
): number | undefined {
	if ( direction === 'down' ) {
		for ( const node of tree ) {
			if ( ! node.parent || node.parent.data.isExpanded ) {
				if (
					node.index !== undefined &&
					node.index >= highlightedIndex
				) {
					return node.index;
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
				if (
					node.index !== undefined &&
					node.index <= highlightedIndex
				) {
					return node.index;
				}
			}
		}
	}

	return undefined;
}

// Counts the number of nodes in a LinkedTree
export function countNumberOfNodes( linkedTree: LinkedTree[] ) {
	let count = 0;
	for ( const node of linkedTree ) {
		count++;
		if ( node.children ) {
			count += countNumberOfNodes( node.children );
		}
	}
	return count;
}

// Gets the data of a node by its index
export function getNodeDataByIndex(
	linkedTree: LinkedTree[],
	index: number
): Item | undefined {
	for ( const node of linkedTree ) {
		if ( node.index === index ) {
			return node.data;
		}
		if ( node.children ) {
			const child = getNodeDataByIndex( node.children, index );
			if ( child ) {
				return child;
			}
		}
	}
	return undefined;
}
