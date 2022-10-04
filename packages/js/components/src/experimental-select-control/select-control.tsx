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
import {
	ChildrenType,
	DefaultItemType,
	getItemLabelType,
	getItemValueType,
} from './types';
import { SelectedItems } from './selected-items';
import { ComboBox } from './combo-box';
import { Menu } from './menu';
import { MenuItem } from './menu-item';
import {
	defaultGetItemLabel,
	defaultGetItemValue,
	defaultGetFilteredItems,
} from './utils';

type SelectControlProps< ItemType > = {
	children?: ChildrenType< ItemType >;
	items: ItemType[];
	label: string;
	initialSelectedItems?: ItemType[];
	getItemLabel?: getItemLabelType< ItemType >;
	getItemValue?: getItemValueType< ItemType >;
	getFilteredItems?: (
		allItems: ItemType[],
		inputValue: string,
		selectedItems: ItemType[],
		getItemLabel: getItemLabelType< ItemType >
	) => ItemType[];
	multiple?: boolean;
	onInputChange?: ( value: string | undefined ) => void;
	onRemove?: ( item: ItemType ) => void;
	onSelect?: ( selected: ItemType ) => void;
	placeholder?: string;
	selected: ItemType | ItemType[] | null;
};

function SelectControl< ItemType = DefaultItemType >( {
	getItemLabel = defaultGetItemLabel,
	getItemValue = defaultGetItemValue,
	children = ( {
		items: renderItems,
		highlightedIndex,
		getItemProps,
		getMenuProps,
		isOpen,
	} ) => {
		return (
			<Menu getMenuProps={ getMenuProps } isOpen={ isOpen }>
				{ renderItems.map( ( item, index: number ) => (
					<MenuItem
						key={ `${ getItemValue( item ) }${ index }` }
						index={ index }
						isActive={ highlightedIndex === index }
						item={ item }
						getItemProps={ getItemProps }
					>
						{ getItemLabel( item ) }
					</MenuItem>
				) ) }
			</Menu>
		);
	},
	multiple = false,
	items,
	label,
	getFilteredItems = defaultGetFilteredItems,
	onInputChange = () => null,
	onRemove = () => null,
	onSelect = () => null,
	placeholder,
	selected,
}: SelectControlProps< ItemType > ) {
	const [ isFocused, setIsFocused ] = useState( false );
	const [ inputValue, setInputValue ] = useState( '' );
	const {
		addSelectedItem,
		getSelectedItemProps,
		getDropdownProps,
		removeSelectedItem,
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
	} = useMultipleSelection( { itemToString: getItemLabel } );
	let selectedItems = selected === null ? [] : selected;
	selectedItems = Array.isArray( selectedItems )
		? selectedItems
		: [ selectedItems ].filter( Boolean );
	const filteredItems = getFilteredItems(
		items,
		inputValue,
		selectedItems,
		getItemLabel
	);

	const {
		isOpen,
		getLabelProps,
		getMenuProps,
		getInputProps,
		getComboboxProps,
		highlightedIndex,
		getItemProps,
		selectItem,
		selectedItem: singleSelectedItem,
	} = useCombobox< ItemType | null >( {
		inputValue,
		items: filteredItems,
		itemToString: getItemLabel,
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
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
						if ( multiple ) {
							addSelectedItem( selectedItem );
							setInputValue( '' );
							break;
						}

						selectItem( selectedItem );
						setInputValue( getItemLabel( selectedItem ) );
					}

					if ( ! selectedItem && ! multiple ) {
						setInputValue( getItemLabel( singleSelectedItem ) );
					}

					break;
				default:
					break;
			}
		},
	} );

	const onRemoveItem = ( item: ItemType ) => {
		selectItem( null );
		removeSelectedItem( item );
		onRemove( item );
	};

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
						getItemLabel={ getItemLabel }
						getItemValue={ getItemValue }
						getSelectedItemProps={ getSelectedItemProps }
						onRemove={ onRemoveItem }
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
				getItemLabel,
				getItemValue,
			} ) }
		</div>
	);
}

export { SelectControl };
