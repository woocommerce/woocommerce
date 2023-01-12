/**
 * External dependencies
 */
import React, { useEffect, useMemo, useState } from 'react';

/**
 * Internal dependencies
 */
import { Item, CheckedStatus, TreeItemProps } from '../types';

function isIndeterminate( selectedItems: Item[], children?: Item[] ): boolean {
	if ( children?.length ) {
		for ( const child of children ) {
			const isChildSelected = selectedItems.some(
				( { value } ) => value === child.value
			);
			if (
				! isChildSelected ||
				isIndeterminate( selectedItems, child.children )
			) {
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

export function useTreeItem( {
	item,
	selected,
	multiple,
	level,
	getLabel,
	isExpanded,
	isHighlighted,
	onSelect,
	onRemove,
	...props
}: TreeItemProps ) {
	const [ expanded, setExpanded ] = useState( false );

	useEffect( () => {
		if ( item.children?.length ) {
			setExpanded(
				typeof isExpanded === 'function' && isExpanded( item )
			);
		}
	}, [ item, isExpanded ] );

	const checkedStatus: CheckedStatus = useMemo( () => {
		if ( multiple && Array.isArray( selected ) ) {
			const selectedItem = selected.some(
				( { value } ) => value === item.value
			);
			if ( selectedItem ) {
				if ( isIndeterminate( selected, item.children ) ) {
					return 'indeterminate';
				}
				return 'checked';
			}
			return 'unchecked';
		}

		return selected === item ? 'checked' : 'unchecked';
	}, [ selected, item, multiple ] );

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
			const selectedMap = ( selected as Item[] ).reduce(
				( selection, current ) => ( {
					...selection,
					[ current.value ]: current,
				} ),
				{} as Record< string, Item >
			);
			const hasSelectedSibblingChildren =
				Array.isArray( selected ) &&
				item.children.some( ( child ) => {
					const selectedItem = selectedMap[ child.value ];
					return (
						selectedItem &&
						! ( Array.isArray( value )
							? value.some(
									( childValue ) =>
										childValue.value === selectedItem.value
							  )
							: value === selectedItem )
					);
				} );
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
		let value: Item | Item[];

		if ( multiple && item.children?.length ) {
			value = [ item, ...getDeepChildren( item ) ];
		} else {
			value = item;
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
		highlighted:
			typeof isHighlighted === 'function'
				? isHighlighted( item )
				: undefined,
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
