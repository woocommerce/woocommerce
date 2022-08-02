/**
 * External dependencies
 */
import classnames from 'classnames';
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
import './style.scss';

type SearchControlProps = {
	children?: ChildrenType;
	items: ItemType[];
	label: string;
	hasMultiple?: boolean;
	initialSelectedItems?: ItemType[];
	itemToString?: ( item: ItemType | null ) => string;
	getFilteredItems?: (
		allItems: ItemType[],
		inputValue: string,
		selectedItems: ItemType[]
	) => ItemType[];
	onInputChange?: ( value: string | undefined ) => void;
	onRemove?: ( item: ItemType ) => void;
	onSelect?: ( selected: ItemType | null | undefined ) => void;
	selected: ItemType | ItemType[];
};

export const SearchControl = ( {
	children,
	hasMultiple = false,
	items,
	label,
	itemToString = defaultItemToString,
	getFilteredItems = defaultGetFilteredItems,
	onInputChange = () => null,
	onRemove = () => null,
	onSelect = () => null,
	selected,
}: SearchControlProps ) => {
	const [ isFocused, setIsFocused ] = useState( false );
	const [ inputValue, setInputValue ] = useState( '' );
	const { getSelectedItemProps, getDropdownProps } = useMultipleSelection();
	const selectedItems = Array.isArray( selected ) ? selected : [ selected ];
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
							hasMultiple ? '' : itemToString( selectedItem )
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
			className={ classnames( 'woocommerce-search-control', {
				'is-focused': isFocused,
			} ) }
		>
			{ /* Downshift's getLabelProps handles the necessary label attributes. */ }
			{ /* eslint-disable jsx-a11y/label-has-for */ }
			<label { ...getLabelProps() }>{ label }</label>
			{ /* eslint-enable jsx-a11y/label-has-for */ }
			<div className="woocommerce-search-control__combo-box-wrapper">
				{ hasMultiple && (
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
						className: 'woocommerce-search-control__input',
						onFocus: () => setIsFocused( true ),
						onBlur: () => setIsFocused( false ),
					} ) }
				/>
			</div>
			<Menu
				children={ children }
				menuProps={ getMenuProps( {
					className: 'woocommerce-search-control__menu',
				} ) }
				items={ filteredItems }
				highlightedIndex={ highlightedIndex }
				isOpen={ isOpen }
				getItemProps={ getItemProps }
			/>
		</div>
	);
};
