/**
 * External dependencies
 */
import { ReactElement, Component } from 'react';

export type ChildrenProps< Item > = {
	items: Item[];
	isListboxOpen: boolean;
	getItemLabel: getItemLabelType< Item >;
	getItemValue: getItemValueType< Item >;
	selectItem: ( item: Item ) => void;
};
export type Children< Item > = ( {
	items,
	isListboxOpen,
}: ChildrenProps< Item > ) => ReactElement | Component;

export type DefaultItem = {
	label: string;
	value: string | number;
};

export type getItemLabelType< Item > = ( item: Item ) => string;

export type getItemValueType< Item > = ( item: Item ) => string | number;
