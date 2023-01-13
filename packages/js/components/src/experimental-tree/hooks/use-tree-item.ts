/**
 * External dependencies
 */
import React, { useEffect, useMemo, useState } from 'react';

/**
 * Internal dependencies
 */
import { Item, CheckedStatus, TreeItemProps } from '../types';

let selectedItemsMap: Record< string, number > = {};
let indeterminateMemo: Record< string, boolean > = {};

function isIndeterminate(
	selectedItems: Record< string, number >,
	children?: Item[],
	memo: Record< string, boolean > = indeterminateMemo
): boolean {
	if ( children?.length ) {
		for ( const child of children ) {
			if ( child.value in indeterminateMemo ) {
				return true;
			}
			const isChildSelected = child.value in selectedItems;
			if (
				! isChildSelected ||
				isIndeterminate( selectedItems, child.children, memo )
			) {
				indeterminateMemo[ child.value ] = true;
				return true;
			}
		}
	}
	return false;
}

function getDeepChildren( value: Item ) {
	if ( value.children?.length ) {
		const children = [ ...value.children ];
		value.children.forEach( ( child ) => {
			children.push( ...getDeepChildren( child ) );
		} );
		return children;
	}
	return [];
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

export function useTreeItem( {
	item,
	selected,
	multiple,
	level,
	index,
	getLabel,
	isExpanded,
	isHighlighted,
	onSelect,
	onRemove,
	...props
}: TreeItemProps ) {
	const [ expanded, setExpanded ] = useState( false );

	const selectedItems = useMemo( () => {
		if ( level === 1 && index === 0 ) {
			selectedItemsMap = mapSelectedItems( selected );
			indeterminateMemo = {} as Record< string, boolean >;
		}
		return selectedItemsMap;
	}, [ selected, level, index ] );

	const checkedStatus: CheckedStatus = useMemo( () => {
		if ( item.value in selectedItems ) {
			if ( multiple && isIndeterminate( selectedItems, item.children ) ) {
				return 'indeterminate';
			}
			return 'checked';
		}
		return 'unchecked';
	}, [ selectedItems, item, multiple ] );

	const highlighted = useMemo( () => {
		if ( multiple ) {
			if ( typeof isHighlighted === 'function' ) {
				return isHighlighted( item );
			}
		} else {
			return checkedStatus === 'checked';
		}
	}, [ item, multiple, checkedStatus, isHighlighted ] );

	useEffect( () => {
		if ( item.children?.length && typeof isExpanded === 'function' ) {
			setExpanded( isExpanded( item ) );
		}
	}, [ item, isExpanded ] );

	function onSelectChildren( value: Item | Item[] ) {
		if ( typeof onSelect !== 'function' ) return;

		if ( multiple ) {
			if ( Array.isArray( value ) ) {
				onSelect( [ item, ...value ] );
			} else {
				onSelect( [ item, value ] );
			}
		} else {
			onSelect( value );
		}
	}

	function onRemoveChildren( value: Item | Item[] ) {
		if ( typeof onRemove !== 'function' ) return;

		if ( multiple && item.children?.length ) {
			const hasSelectedSibblingChildren = item.children.some(
				( child ) => {
					const isChildSelected = child.value in selectedItems;
					if ( ! isChildSelected ) return false;
					return ! ( Array.isArray( value )
						? value.some(
								( childValue ) =>
									childValue.value === child.value
						  )
						: value.value === child.value );
				}
			);
			if ( hasSelectedSibblingChildren ) {
				onRemove( value );
			} else if ( Array.isArray( value ) ) {
				onRemove( [ item, ...value ] );
			} else {
				onRemove( [ item, value ] );
			}
		} else {
			onRemove( value );
		}
	}

	function onSelectChild( checked: boolean ) {
		let value: Item | Item[] = item;

		if ( multiple ) {
			if ( item.children?.length ) {
				value = [ item, ...getDeepChildren( item ) ];
			}
		} else if ( item.children?.length ) {
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

	function onKeyDown( event: React.KeyboardEvent< HTMLDivElement > ) {
		if ( event.code === 'ArrowRight' ) {
			event.preventDefault();

			setExpanded( true );
		}

		if ( event.code === 'ArrowLeft' ) {
			event.preventDefault();

			setExpanded( false );
		}

		if ( event.code === 'Enter' ) {
			event.preventDefault();

			setExpanded( ( prev ) => ! prev );
		}
	}

	function onToggleExpand() {
		setExpanded( ( prev ) => ! prev );
	}

	const treeId = `experimental-woocommerce-tree__group-${ item.value }`;

	return {
		item,
		multiple,
		expanded,
		checkedStatus,
		highlighted,
		getLabel,
		onToggleExpand,
		onSelectChild,

		treeItem: props,
		headingProps: {
			onKeyDown,
			style: { paddingLeft: ( level - 1 ) * 28 + 12 },
			'aria-selected': checkedStatus !== 'unchecked',
			'aria-expanded': item.children?.length ? expanded : undefined,
			'aria-owns': item.children?.length && expanded ? treeId : undefined,
		},
		treeProps: {
			id: treeId,
			items: item.children ?? [],
			selected,
			level: level + 1,
			multiple,
			onSelect: onSelectChildren,
			onRemove: onRemoveChildren,
			getItemLabel: getLabel,
			isItemExpanded: isExpanded,
			isItemHighlighted: isHighlighted,
			'aria-label': item.label,
		},
	};
}
