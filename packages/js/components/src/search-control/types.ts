/**
 * External dependencies
 */
import { ReactElement, Component } from 'react';

export type ItemType = {
	value: string;
};

export type Props = {
	[ key: string ]: string;
};

export type ChildrenProps = {
	items: ItemType[];
	isOpen: boolean;
	highlightedIndex: number;
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore These are the types provided by Downshift.
	getItemProps: ( { item: any, index: any } ) => Props;
};

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore These are the types provided by Downshift.
export type ChildrenType = ( {
	items,
	isOpen,
	highlightedIndex,
}: ChildrenProps ) => ReactElement | Component;
