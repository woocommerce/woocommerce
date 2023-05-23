/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { DefaultItem, getItemLabelType, getItemValueType } from '../types';
import { useCombobox } from './use-combobox';
import { useItem } from './use-item';
import { useFilter } from './use-filter';
import { useListbox } from './use-listbox';

type useDropdownProps< Item > = {
	getItemLabel: getItemLabelType< Item >;
	getItemValue?: getItemValueType< Item >;
	initialSelected?: Item | Item[];
	multiple?: boolean;
	onDeselect?: ( item: Item ) => void;
	options: Item[];
	onSelect?: ( item: Item ) => void;
};

export function useDropdown< Item = DefaultItem >( {
	getItemLabel,
	initialSelected,
	multiple = false,
	options,
	onDeselect = () => null,
	onSelect = () => null,
}: useDropdownProps< Item > ) {
	const [ inputValue, setInputValue ] = useState< string >( '' );

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

	const { filteredOptions } = useFilter< Item >( {
		getItemLabel,
		inputValue,
		options,
		selected,
	} );
	const {
		close: closeListbox,
		props: listboxProps,
		highlightedOption,
		highlightNextOption,
		highlightPreviousOption,
		isOpen: isListboxOpen,
		open: openListbox,
	} = useListbox< Item >( {
		multiple,
		options: filteredOptions,
	} );
	const comboboxProps = useCombobox< Item >( {
		closeListbox,
		highlightedOption,
		highlightNextOption,
		highlightPreviousOption,
		openListbox,
		selectItem,
		setInputValue,
	} );
	const { getItemProps } = useItem< Item >( {
		deselectItem,
		highlightedOption,
		multiple,
		selected,
		selectItem,
	} );

	return {
		comboboxProps,
		filteredOptions,
		getItemProps,
		inputValue,
		isListboxOpen,
		listboxProps,
		selected: getSelected(),
		selectItem,
		deselectItem,
		setInputValue,
	};
}
