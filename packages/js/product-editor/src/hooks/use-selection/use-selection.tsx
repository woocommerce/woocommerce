/**
 * External dependencies
 */
import { useMemo, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Selection, UseSelectionProps } from './types';

export function useSelection< T >( { getId }: UseSelectionProps< T > ) {
	const [ selection, setSelection ] = useState< Selection< T > >( {} );

	const selectedItems = useMemo(
		function getSelectedItems() {
			return Object.values( selection ).filter(
				( value ) => value
			) as T[];
		},
		[ selection ]
	);

	function isSelected( item: T ) {
		return Boolean( selection[ getId( item ) ] );
	}

	function areAllSelected( items: T[] ) {
		return items.every( ( item ) => isSelected( item ) );
	}

	function hasSelection( items: T[] ) {
		return items.some( ( item ) => isSelected( item ) );
	}

	function onSelectItem( item: T ) {
		return function onChange( isChecked: boolean ) {
			setSelection( ( currentSelection ) => ( {
				...currentSelection,
				[ getId( item ) ]: isChecked ? item : undefined,
			} ) );
		};
	}

	function onSelectAll( items: T[] ) {
		return function onChange( isChecked: boolean ) {
			setSelection( ( currentSelection ) => {
				const newSelection = items.reduce< Selection< T > >(
					( current, item ) => ( {
						...current,
						[ getId( item ) ]: isChecked ? item : undefined,
					} ),
					currentSelection
				);
				return newSelection;
			} );
		};
	}

	function onClearSelection() {
		setSelection( {} );
	}

	return {
		selectedItems,
		areAllSelected,
		hasSelection,
		isSelected,
		onSelectItem,
		onSelectAll,
		onClearSelection,
	};
}
