/**
 * External dependencies
 */
import classnames from 'classnames';
import { createElement } from 'react';
import { useCombobox, useMultipleSelection } from 'downshift';
import { useState, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ChildrenType, ItemType } from './types';
import { SelectedItems } from './selected-items';
import { ComboBox } from './combo-box';
import { Menu } from './menu';
import { MenuItem } from './menu-item';
import {
	itemToString as defaultItemToString,
	getFilteredItems as defaultGetFilteredItems,
} from './utils';

type SelectControlProps = {
	children?: ChildrenType;
	items: ItemType[];
	label: string;
	initialSelectedItems?: ItemType[];
	itemToString?: ( item: ItemType | null ) => string;
	getFilteredItems?: (
		allItems: ItemType[],
		inputValue: string,
		selectedItems: ItemType[]
	) => ItemType[];
	multiple?: boolean;
	onInputChange?: ( value: string | undefined ) => void;
	onRemove?: ( item: ItemType ) => void;
	onSelect?: ( selected: ItemType ) => void;
	placeholder?: string;
	selected: ItemType | ItemType[] | null;
};

export const SelectControl = ( {
	children = ( {
		items,
		highlightedIndex,
		getItemProps,
		getMenuProps,
		isOpen,
	} ) => {
		return (
			<Menu getMenuProps={ getMenuProps } isOpen={ isOpen }>
				{ items.map( ( item, index: number ) => (
					<MenuItem
						key={ `${ item.value }${ index }` }
						index={ index }
						isActive={ highlightedIndex === index }
						item={ item }
						getItemProps={ getItemProps }
					>
						{ item.label }
					</MenuItem>
				) ) }
			</Menu>
		);
	},
	multiple = false,
	items,
	label,
	itemToString = defaultItemToString,
	getFilteredItems = defaultGetFilteredItems,
	onInputChange = () => null,
	onRemove = () => null,
	onSelect = () => null,
	placeholder,
	selected,
}: SelectControlProps ) => {
	const [ isFocused, setIsFocused ] = useState( false );
	const [ inputValue, setInputValue ] = useState( '' );
	const { getSelectedItemProps, getDropdownProps } = useMultipleSelection();
	let selectedItems = selected === null ? [] : selected;
	selectedItems = Array.isArray( selectedItems )
		? selectedItems
		: [ selectedItems ].filter( Boolean );
	const filteredItems = getFilteredItems( items, inputValue, selectedItems );

	const {
		isOpen,
		getLabelProps,
		getMenuProps,
		getInputProps,
		getComboboxProps,
		highlightedIndex,
		getItemProps,
	} = useCombobox( {
		inputValue,
		items: filteredItems,
		itemToString,
		selectedItem: null,
		onStateChange: ( { inputValue: value, type, selectedItem } ) => {
			switch ( type ) {
				case useCombobox.stateChangeTypes.InputChange:
					onInputChange( value );
					setInputValue( value || '' );

					break;
				case useCombobox.stateChangeTypes.InputKeyDownEnter:
				case useCombobox.stateChangeTypes.ItemClick:
				case useCombobox.stateChangeTypes.InputBlur:
					if ( selectedItem ) {
						onSelect( selectedItem );
						setInputValue(
							multiple ? '' : itemToString( selectedItem )
						);
					}

					break;
				default:
					break;
			}
		},
	} );

	return (
		<div
			className={ classnames( 'woocommerce-experimental-select-control', {
				'is-focused': isFocused,
			} ) }
		>
			{ /* Downshift's getLabelProps handles the necessary label attributes. */ }
			{ /* eslint-disable jsx-a11y/label-has-for */ }
			<label { ...getLabelProps() }>{ label }</label>
			{ /* eslint-enable jsx-a11y/label-has-for */ }
			<div className="woocommerce-experimental-select-control__combo-box-wrapper">
				{ multiple && (
					<SelectedItems
						items={ selectedItems }
						itemToString={ itemToString }
						getSelectedItemProps={ getSelectedItemProps }
						onRemove={ onRemove }
					/>
				) }
				<ComboBox
					comboBoxProps={ getComboboxProps() }
					inputProps={ getInputProps( {
						...getDropdownProps( { preventKeyAction: isOpen } ),
						className:
							'woocommerce-experimental-select-control__input',
						onFocus: () => setIsFocused( true ),
						onBlur: () => setIsFocused( false ),
						placeholder,
					} ) }
				/>
			</div>

			{ children( {
				items: filteredItems,
				highlightedIndex,
				getItemProps,
				getMenuProps,
				isOpen,
			} ) }
		</div>
	);
};
