/**
 * External dependencies
 */
import React from 'react';
import { useState } from '@wordpress/element';
import classNames from 'classnames';
import {
	useCombobox,
	UseComboboxState,
	UseComboboxStateChangeOptions,
	useMultipleSelection,
} from 'downshift';

/**
 * Internal dependencies
 */
import type { SelectedItemsProps } from '../selected-items';
import type {
	ChildrenType,
	getItemLabelType,
	getItemValueType,
	Props,
} from '../types';
import { defaultGetItemLabel, defaultGetItemValue } from '../utils';

export const selectControlStateChangeTypes = useCombobox.stateChangeTypes;

export default function useDownshiftCombobox< T >( {
	label,
	items,
	selected,
	placeholder,
	disabled,
	className,
	children,
	hasExternalTags = false,
	multiple = false,
	__experimentalOpenMenuOnFocus = false,
	stateReducer = ( _, actionAndChanges ) => actionAndChanges.changes,
	getItemLabel = defaultGetItemLabel,
	getItemValue = defaultGetItemValue,
	onInputChange = () => {},
	onSelect = () => {},
	onRemove = () => {},
	onFocus = () => {},
	...props
}: UseDownshiftComboboxInput< T > ): UseDownshiftComboboxOutput< T > {
	const [ isFocused, setIsFocused ] = useState( false );

	const selectedItems = Array.isArray( selected )
		? selected
		: ( [ selected ].filter( Boolean ) as T[] );

	const singleSelectedItem =
		! multiple && selectedItems.length ? selectedItems[ 0 ] : null;

	const { getSelectedItemProps, getDropdownProps, removeSelectedItem } =
		useMultipleSelection( { itemToString: getItemLabel, selectedItems } );

	const {
		isOpen,
		getLabelProps,
		getMenuProps,
		getInputProps,
		getComboboxProps,
		highlightedIndex,
		getItemProps,
		selectItem,
		reset,
		selectedItem: comboboxSingleSelectedItem,
		openMenu,
		closeMenu,
	} = useCombobox< T >( {
		initialSelectedItem: singleSelectedItem,
		items,
		selectedItem: multiple ? null : singleSelectedItem,
		itemToString: getItemLabel,
		onSelectedItemChange: ( { selectedItem } ) =>
			selectedItem && onSelect( selectedItem ),
		onInputValueChange: ( { inputValue: value } ) => {
			onInputChange( value );
		},
		stateReducer: ( state, actionAndChanges ) => {
			const { changes, type } = actionAndChanges;
			let newChanges;
			switch ( type ) {
				case selectControlStateChangeTypes.InputBlur:
					// Set input back to selected item if there is a selected item, blank otherwise.
					newChanges = {
						...changes,
						inputValue:
							changes.selectedItem === state.selectedItem &&
							! multiple
								? getItemLabel( comboboxSingleSelectedItem )
								: '',
					};
					break;
				case selectControlStateChangeTypes.InputKeyDownEnter:
				case selectControlStateChangeTypes.FunctionSelectItem:
				case selectControlStateChangeTypes.ItemClick:
					if ( changes.selectedItem && multiple ) {
						newChanges = {
							...changes,
							inputValue: '',
						};
					}
					break;
				default:
					break;
			}
			return stateReducer( state, {
				...actionAndChanges,
				changes: newChanges ?? changes,
			} );
		},
	} );

	const onRemoveItem = ( item: T ) => {
		reset();
		removeSelectedItem( item );
		onRemove( item );
	};

	return {
		...props,
		hasExternalTags,
		className: classNames( className, { 'is-focused': isFocused } ),
		labelProps: getLabelProps( { children: label } ),
		comboBoxProps: getComboboxProps(),
		inputProps: getInputProps(
			getDropdownProps( {
				placeholder,
				disabled,
				preventKeyAction: isOpen,
				onFocus: () => {
					setIsFocused( true );
					onFocus();
					if ( __experimentalOpenMenuOnFocus ) {
						openMenu();
					}
				},
				onBlur: () => setIsFocused( false ),
			} )
		),
		selectedItemsProps: {
			items: selectedItems,
			getItemLabel,
			getItemValue,
			getSelectedItemProps,
			onRemove: onRemoveItem,
		},
		children:
			typeof children == 'function'
				? children( {
						items,
						isOpen,
						highlightedIndex,
						getItemLabel,
						getItemProps,
						getItemValue,
						closeMenu,
						getMenuProps,
						openMenu,
						selectItem,
				  } )
				: children,
	};
}

export type UseDownshiftComboboxInput< T > = {
	children?: ChildrenType< T >;
	items: T[];
	label: string | JSX.Element;
	getItemLabel?: getItemLabelType< T >;
	getItemValue?: getItemValueType< T >;
	hasExternalTags?: boolean;
	multiple?: boolean;
	onInputChange?: ( value?: string ) => void;
	onRemove?: ( item: T ) => void;
	onSelect?: ( selected: T ) => void;
	onFocus?: () => void;
	stateReducer?: (
		state: UseComboboxState< T | null >,
		actionAndChanges: UseComboboxStateChangeOptions< T | null >
	) => Partial< UseComboboxState< T | null > >;
	placeholder?: string;
	selected: T | T[] | null;
	className?: string;
	disabled?: boolean;
	suffix?: JSX.Element | null;
	/**
	 * This is a feature already implemented in downshift@7.0.0 through the
	 * reducer. In order for us to use it this prop is added temporarily until
	 * current downshift version get updated.
	 *
	 * @see https://www.downshift-js.com/use-multiple-selection#usage-with-combobox
	 * @default false
	 */
	__experimentalOpenMenuOnFocus?: boolean;
};

export type UseDownshiftComboboxOutput< T > = React.PropsWithChildren< {
	labelProps: React.DetailedHTMLProps<
		React.LabelHTMLAttributes< HTMLLabelElement >,
		HTMLLabelElement
	>;
	comboBoxProps: Props;
	inputProps: Props;
	selectedItemsProps: SelectedItemsProps< T >;
	className?: string;
	hasExternalTags?: boolean;
	suffix?: JSX.Element | null;
} >;
