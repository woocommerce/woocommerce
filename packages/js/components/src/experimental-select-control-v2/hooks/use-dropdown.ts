/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { DefaultItem, getItemLabelType, getItemValueType } from '../types';
import { useCombobox } from './use-combobox';
import { useListbox } from './use-listbox';

type useDropdownProps< Item > = {
	getItemLabel?: getItemLabelType< Item >;
	getItemValue?: getItemValueType< Item >;
	initialSelected?: Item | Item[];
	items: Item[];
	multiple?: boolean;
	onDeselect?: ( item: Item ) => void;
	onSelect?: ( item: Item ) => void;
};

export function useDropdown< Item = DefaultItem >( {
	getItemLabel = ( item: Item ) => ( item as DefaultItem ).label,
	initialSelected,
	items,
	multiple = false,
	onDeselect = () => null,
	onSelect = () => null,
}: useDropdownProps< Item > ) {
	function getInitialSelected(): Item[] {
		if ( ! initialSelected ) {
			return [];
		}
		if ( Array.isArray( initialSelected ) ) {
			return initialSelected;
		}
		return [ initialSelected ];
	}

	const [ selected, setSelected ] = useState< Item[] >(
		getInitialSelected()
	);

	function getSelected() {
		if ( multiple ) {
			return selected;
		}
		return selected.length ? selected[ 0 ] : null;
	}

	function deselectItem( item: Item ) {
		setSelected( selected.filter( ( i ) => i !== item ) );
		onDeselect( item );
	}

	function selectItem( item: Item ) {
		setSelected( [ ...selected, item ] );
		onSelect( item );
		if ( ! multiple ) {
			setInputValue( getItemLabel( item ) );
		}
	}

	const {
		close: closeListbox,
		props: listboxProps,
		highlightedOption,
		highlightNextOption,
		highlightPreviousOption,
		isOpen: isListboxOpen,
		open: openListbox,
	} = useListbox( {
		multiple,
		options: items,
	} );
	const {
		props: comboboxProps,
		value: inputValue,
		setValue: setInputValue,
	} = useCombobox( {
		closeListbox,
		highlightedOption,
		highlightNextOption,
		highlightPreviousOption,
		openListbox,
		selectItem,
	} );

	return {
		comboboxProps,
		inputValue,
		isListboxOpen,
		listboxProps,
		selected: getSelected(),
		selectItem,
		deselectItem,
		setInputValue,
	};
}
