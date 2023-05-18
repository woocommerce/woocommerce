/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

export type DefaultItemType = {
	label: string;
	value: string | number;
};

type useDropdownProps< Item > = {
	getItemLabel?: ( item: Item ) => string;
	getItemValue?: ( item: Item ) => string | number;
	initialSelected?: Item | Item[];
	items: Item[];
	multiple?: boolean;
	onDeselect?: ( item: Item ) => void;
	onSelect?: ( item: Item ) => void;
};

export function useDropdown< Item = DefaultItemType >( {
	getItemLabel = ( item: Item ) => ( item as DefaultItemType ).label,
	initialSelected,
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
	const [ inputValue, setInputValue ] = useState< string >( '' );

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

	return {
		inputValue,
		selected: getSelected(),
		selectItem,
		deselectItem,
	};
}
