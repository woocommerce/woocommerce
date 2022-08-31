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
	value: string;
	label: string;
};

export type SelectedType< ItemType > = ItemType | null;

export type Props = {
	[ key: string ]: string;
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
};

export type ChildrenType< ItemType > = ( {
	items,
	isOpen,
	highlightedIndex,
}: ChildrenProps< ItemType > ) => ReactElement | Component;
