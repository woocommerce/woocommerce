/**
 * External dependencies
 */
import { ReactElement, Component } from 'react';
import {
	UseComboboxGetItemPropsOptions,
	UseComboboxGetMenuPropsOptions,
	GetPropsCommonOptions,
} from 'downshift';

export type ItemType = {
	value: string;
	label: string;
};

export type SelectedType = ItemType | null;

export type Props = {
	[ key: string ]: string;
};

export type getItemPropsType = (
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

export type ChildrenProps = {
	items: ItemType[];
	isOpen: boolean;
	highlightedIndex: number;
	getItemProps: getItemPropsType;
	getMenuProps: getMenuPropsType;
};

export type ChildrenType = ( {
	items,
	isOpen,
	highlightedIndex,
}: ChildrenProps ) => ReactElement | Component;
