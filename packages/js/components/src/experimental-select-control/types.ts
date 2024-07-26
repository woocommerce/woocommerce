/**
 * External dependencies
 */
import { ReactElement, Component } from 'react';
import {
	UseComboboxGetItemPropsOptions,
	UseComboboxGetMenuPropsOptions,
	GetPropsCommonOptions,
} from 'downshift';

export type DefaultItemType = {
	label: string;
	value: string | number;
};

export type SelectedType< ItemType > = ItemType | null;

export type Props = {
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	[ key: string ]: any;
};

export type getItemPropsType< ItemType > = (
	options: UseComboboxGetItemPropsOptions< ItemType >
	// These are the types provided by Downshift.
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
) => any;

export type getMenuPropsType = (
	options?: UseComboboxGetMenuPropsOptions,
	otherOptions?: GetPropsCommonOptions
	// These are the types provided by Downshift.
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
) => any;

export type ChildrenProps< ItemType > = {
	items: ItemType[];
	isOpen: boolean;
	highlightedIndex: number;
	getItemProps: getItemPropsType< ItemType >;
	getMenuProps: getMenuPropsType;
	getItemLabel: getItemLabelType< ItemType >;
	getItemValue: getItemValueType< ItemType >;
	selectItem: ( item: ItemType ) => void;
	setInputValue: ( value: string ) => void;
	openMenu: () => void;
	closeMenu: () => void;
};

export type ChildrenType< ItemType > = ( {
	items,
	isOpen,
	highlightedIndex,
}: ChildrenProps< ItemType > ) => ReactElement | Component;

export type getItemLabelType< ItemType > = ( item: ItemType | null ) => string;

export type getItemValueType< ItemType > = (
	item: ItemType | null
) => string | number;

export type SelectedItemFocusHandle = () => void;
