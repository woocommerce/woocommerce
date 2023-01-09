/**
 * External dependencies
 */
import React, { useEffect, useMemo, useState } from 'react';

/**
 * Internal dependencies
 */
import { AutocompleteItem, CheckedStatus, MenuItemProps } from '../types';

function isIndeterminate(
	selectedItems: AutocompleteItem[],
	children?: AutocompleteItem[]
): boolean {
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

function getDeepChildren( value: AutocompleteItem ) {
	if ( value.children?.length ) {
		const children = [ ...value.children ];
		value.children.forEach( ( child ) => {
			children.push( ...getDeepChildren( child ) );
		} );
		return children;
	}
	return [];
}

export function useMenuItem( {
	item,
	selected,
	multiple,
	inputValue,
	onSelect,
	onRemove,
}: MenuItemProps ) {
	const [ expanded, setExpanded ] = useState( false );

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

	const highlighted = useMemo( () => {
		return inputValue && new RegExp( inputValue, 'ig' ).test( item.label );
	}, [ inputValue, item ] );

	useEffect( () => {
		if ( item.children?.length ) {
			if ( multiple ) {
				if (
					inputValue &&
					new RegExp( inputValue, 'ig' ).test( item.label )
				) {
					setExpanded( inputValue !== item.label );
				} else {
					setExpanded( Boolean( inputValue ) );
				}
			} else {
				if (
					inputValue &&
					( selected as AutocompleteItem )?.label !== inputValue
				) {
					setExpanded( true );
				}
			}
		}
	}, [ inputValue, item, multiple, selected ] );

	function onSelectChildren( value: AutocompleteItem | AutocompleteItem[] ) {
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

	function onRemoveChildren( value: AutocompleteItem | AutocompleteItem[] ) {
		if ( multiple && item.children?.length ) {
			const selectedMap = ( selected as AutocompleteItem[] ).reduce(
				( selection, current ) => ( {
					...selection,
					[ current.value ]: current,
				} ),
				{} as Record< string, AutocompleteItem >
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
		let value: AutocompleteItem | AutocompleteItem[];

		if ( multiple && item.children?.length ) {
			value = [ item, ...getDeepChildren( item ) ];
		} else {
			value = item;
		}

		if ( checked ) {
			onSelect( value );
		} else {
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

	return {
		expanded,
		checkedStatus,
		highlighted,
		onToggleExpand,
		onSelectChild,
		onSelectChildren,
		onRemoveChildren,
		onKeyDown,
	};
}
