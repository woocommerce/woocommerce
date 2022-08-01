/**
 * External dependencies
 */
import { createElement } from 'react';
import { useCombobox, useMultipleSelection } from 'downshift';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ChildrenType, ItemType } from './types';
import { SelectedItems } from './selected-items';
import { ComboBox } from './combo-box';
import { Menu } from './menu';
import {
	itemToString as defaultItemToString,
	getFilteredItems as defaultGetFilteredItems,
} from './utils';

type SearchControlProps = {
	children?: ChildrenType;
	items: ItemType[];
	label: string;
	hasMultiple?: boolean;
	initialSelectedItems?: ItemType[];
	itemToString?: ( item: ItemType | null ) => string;
	getFilteredItems?: (
		allItems: ItemType[],
		selectedItems: ItemType[],
		inputValue: string
	) => ItemType[];
	onInputChange?: ( value: string | undefined ) => void;
};

export const SearchControl = ( {
	children,
	hasMultiple = false,
	items,
	label,
	initialSelectedItems = [],
	itemToString = defaultItemToString,
	getFilteredItems = defaultGetFilteredItems,
	onInputChange = () => null,
}: SearchControlProps ) => {
	const [ inputValue, setInputValue ] = useState( '' );
	const {
		getSelectedItemProps,
		getDropdownProps,
		addSelectedItem,
		removeSelectedItem,
		selectedItems,
	} = useMultipleSelection( {
		initialSelectedItems,
	} );

	const filteredItems = getFilteredItems( items, selectedItems, inputValue );

	const {
		isOpen,
		getToggleButtonProps,
		getLabelProps,
		getMenuProps,
		getInputProps,
		getComboboxProps,
		highlightedIndex,
		getItemProps,
		selectItem,
		selectedItem,
	} = useCombobox( {
		inputValue,
		items: filteredItems,
		itemToString,
		onStateChange: ( {
			inputValue: value,
			type,
			selectedItem: selected,
		} ) => {
			switch ( type ) {
				case useCombobox.stateChangeTypes.InputChange:
					onInputChange( value );
					setInputValue( value || '' );

					break;
				case useCombobox.stateChangeTypes.InputKeyDownEnter:
				case useCombobox.stateChangeTypes.ItemClick:
				case useCombobox.stateChangeTypes.InputBlur:
					if ( selected ) {
						setInputValue(
							hasMultiple ? '' : itemToString( selected )
						);
						addSelectedItem( selected );
						// selectItem( null );
					}

					break;
				default:
					break;
			}
		},
	} );

	return (
		<div className="woocommerce-search-control">
			{ /* Downshift's getLabelProps handles the necessary label attributes. */ }
			{ /* eslint-disable jsx-a11y/label-has-for */ }
			<label { ...getLabelProps() }>{ label }</label>
			{ /* eslint-enable jsx-a11y/label-has-for */ }
			<div>
				{ hasMultiple && (
					<SelectedItems
						items={ selectedItems }
						itemToString={ itemToString }
						getSelectedItemProps={ getSelectedItemProps }
						removeSelectedItem={ removeSelectedItem }
					/>
				) }
				<ComboBox
					comboBoxProps={ getComboboxProps() }
					inputProps={ getInputProps(
						getDropdownProps( { preventKeyAction: isOpen } )
					) }
					toggleButtonProps={ getToggleButtonProps() }
				/>
			</div>
			<Menu
				children={ children }
				menuProps={ getMenuProps() }
				items={ filteredItems }
				highlightedIndex={ highlightedIndex }
				isOpen={ isOpen }
				getItemProps={ getItemProps }
			/>
		</div>
	);
};
