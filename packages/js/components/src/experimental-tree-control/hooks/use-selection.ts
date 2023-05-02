/**
 * External dependencies
 */
import { useMemo } from 'react';

/**
 * Internal dependencies
 */
import { CheckedStatus, Item, LinkedTree, TreeItemProps } from '../types';

let selectedItemsMap: Record< string, number > = {};
let indeterminateMemo: Record< string, boolean > = {};

function getDeepChildren( item: LinkedTree ) {
	if ( item.children.length ) {
		const children = item.children.map( ( { data } ) => data );
		item.children.forEach( ( child ) => {
			children.push( ...getDeepChildren( child ) );
		} );
		return children;
	}
	return [];
}

function isIndeterminate(
	selectedItems: Record< string, number >,
	children?: LinkedTree[],
	memo: Record< string, boolean > = indeterminateMemo
): boolean {
	if ( children?.length ) {
		for ( const child of children ) {
			if ( child.data.value in indeterminateMemo ) {
				return true;
			}
			const isChildSelected = child.data.value in selectedItems;
			if (
				! isChildSelected ||
				isIndeterminate( selectedItems, child.children, memo )
			) {
				indeterminateMemo[ child.data.value ] = true;
				return true;
			}
		}
	}
	return false;
}

function mapSelectedItems(
	selected: Item | Item[] = []
): Record< string, number > {
	const selectedArray = Array.isArray( selected ) ? selected : [ selected ];
	return selectedArray.reduce(
		( map, selectedItem, index ) => ( {
			...map,
			[ selectedItem.value ]: index,
		} ),
		{} as Record< string, number >
	);
}

function hasSelectedSibblingChildren(
	children: LinkedTree[],
	values: Item[],
	selectedItems: Record< string, number >
) {
	return children.some( ( child ) => {
		const isChildSelected = child.data.value in selectedItems;
		if ( ! isChildSelected ) return false;
		return ! values.some(
			( childValue ) => childValue.value === child.data.value
		);
	} );
}

export function useSelection( {
	item,
	multiple,
	shouldNotRecursivelySelect,
	selected,
	level,
	index,
	onSelect,
	onRemove,
}: Pick<
	TreeItemProps,
	| 'item'
	| 'multiple'
	| 'selected'
	| 'level'
	| 'index'
	| 'onSelect'
	| 'onRemove'
	| 'shouldNotRecursivelySelect'
> ) {
	const selectedItems = useMemo( () => {
		if ( level === 1 && index === 0 ) {
			selectedItemsMap = mapSelectedItems( selected );
			indeterminateMemo = {} as Record< string, boolean >;
		}
		return selectedItemsMap;
	}, [ selected, level, index ] );

	const checkedStatus: CheckedStatus = useMemo( () => {
		if ( item.data.value in selectedItems ) {
			if (
				multiple &&
				! shouldNotRecursivelySelect &&
				isIndeterminate( selectedItems, item.children )
			) {
				return 'indeterminate';
			}
			return 'checked';
		}
		return 'unchecked';
	}, [ selectedItems, item, multiple ] );

	function onSelectChild( checked: boolean ) {
		let value: Item | Item[] = item.data;

		if ( multiple ) {
			value = [ item.data ];
			if ( item.children.length && ! shouldNotRecursivelySelect ) {
				value.push( ...getDeepChildren( item ) );
			}
		} else if ( item.children?.length && ! shouldNotRecursivelySelect ) {
			return;
		}

		if ( checked ) {
			if ( typeof onSelect === 'function' ) {
				onSelect( value );
			}
		} else if ( typeof onRemove === 'function' ) {
			onRemove( value );
		}
	}

	function onSelectChildren( value: Item | Item[] ) {
		if ( typeof onSelect !== 'function' ) return;

		if ( multiple && ! shouldNotRecursivelySelect ) {
			value = [ item.data, ...( value as Item[] ) ];
		}

		onSelect( value );
	}

	function onRemoveChildren( value: Item | Item[] ) {
		if ( typeof onRemove !== 'function' ) return;

		if (
			multiple &&
			item.children?.length &&
			! shouldNotRecursivelySelect
		) {
			const hasSelectedSibbling = hasSelectedSibblingChildren(
				item.children,
				value as Item[],
				selectedItems
			);
			if ( ! hasSelectedSibbling ) {
				value = [ item.data, ...( value as Item[] ) ];
			}
		}

		onRemove( value );
	}

	return {
		multiple,
		selected,
		checkedStatus,
		onSelectChild,
		onSelectChildren,
		onRemoveChildren,
	};
}
