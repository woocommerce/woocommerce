/**
 * External dependencies
 */
import type { InputHTMLAttributes, ReactNode } from 'react';
import { Require } from '@woocommerce/types';

interface ItemProps< T extends object = object > {
	// Depth, non-zero if the list is hierarchical.
	depth?: number;
	// Callback for selecting the item.
	onSelect: (
		item: SearchListItem< T > | SearchListItem< T >[]
	) => () => void;
	// Search string, used to highlight the substring in the item name.
	search: string;
	useExpandedPanelId: [
		number,
		React.Dispatch< React.SetStateAction< number > >
	];
}

interface SearchListProps< T extends object = object > {
	//Restrict selections to one item.
	isSingle: boolean;
	// A complete list of item objects, each with id, name properties. This is displayed as a clickable/keyboard-able list, and possibly filtered by the search term (searches name).
	list: SearchListItem< T >[];
	// Callback to render each item in the selection list, allows any custom object-type rendering.
	renderItem?: ( args: RenderItemArgs< T > ) => JSX.Element;
	// The list of currently selected items.
	selected: SearchListItem< T >[];
}

export interface ListItemsProps
	extends Require< SearchListProps, 'renderItem' >,
		ItemProps {
	instanceId: string | number;
}

export type SearchListItem< T extends object = object > = {
	breadcrumbs: string[];
	children?: SearchListItem< T >[];
	count?: number;
	details?: T;
	id: string | number;
	name: string;
	parent: number;
	value: string;
};

export interface SearchListItemsContainerProps< T extends object = object >
	extends SearchListControlProps,
		ItemProps {
	instanceId: string | number;
	filteredList: SearchListItem< T >[];
	messages: SearchListMessages;
}

export interface SearchListMessages {
	// A more detailed label for the "Clear all" button, read to screen reader users.
	clear: string;
	// Message to display when the list is empty (implies nothing loaded from the server or parent component).
	noItems: string;
	// Message to display when no matching results are found. %s is the search term.
	noResults: string;
	// Label for the search input
	search: string;
	// Label for the selected items. This is actually a function, so that we can pass through the count of currently selected items.
	selected: ( n: number ) => string;
	// Label indicating that search results have changed, read to screen reader users.
	updated: string;
}

export interface RenderItemArgs< T extends object = object >
	extends ItemProps,
		Partial<
			Omit<
				InputHTMLAttributes< HTMLInputElement >,
				'onChange' | 'onSelect'
			>
		> {
	// Additional CSS classes.
	className?: string;
	// Unique id of the parent control.
	controlId: string | number;
	// Label to display in the count bubble. Takes preference over `item.count`.
	countLabel?: ReactNode;
	// Whether the item is disabled.
	disabled?: boolean;
	// Current item to display.
	item: SearchListItem< T >;
	// Whether this item is selected.
	isSelected: boolean;
	// Whether this should only display a single item (controls radio vs checkbox icon).
	isSingle: boolean;
	// The list of currently selected items.
	selected: SearchListItem< T >[];
	/**
	 * Name of the inputs. Used to group input controls together. See:
	 * https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input#attr-name
	 * If not provided, a default name will be generated using the controlId.
	 */
	name?: string;
	// Aria label for the input. If not provided, a default label will be generated using the item name.
	ariaLabel?: string;
}

export interface SearchListControlProps< T extends object = object > {
	// Additional CSS classes.
	className?: string;
	// Whether it should be displayed in a compact way, so it occupies less space.
	isCompact: boolean;
	// Whether the list of items is hierarchical or not. If true, each list item is expected to have a parent property.
	isHierarchical?: boolean;
	// Whether the list of items is still loading.
	isLoading?: boolean;
	// Restrict selections to one item.
	isSingle: boolean;
	// A complete list of item objects, each with id, name properties. This is displayed as a clickable/keyboard-able list, and possibly filtered by the search term (searches name).
	list: SearchListItem< T >[];
	// Messages displayed or read to the user. Configure these to reflect your object type.
	messages?: Partial< SearchListMessages >;
	// Callback fired when selected items change, whether added, cleared, or removed. Passed an array of item objects (as passed in via props.list).
	onChange: ( search: SearchListItem< T >[] ) => void;
	// Callback fired when the search field is used.
	onSearch?: ( ( search: string ) => void ) | undefined;
	// Callback to render each item in the selection list, allows any custom object-type rendering.
	renderItem?:
		| ( ( args: RenderItemArgs< T > ) => JSX.Element | null )
		| undefined;
	// The list of currently selected items.
	selected: SearchListItem< T >[];
	// Whether to show a text field or a token field as search
	// Defaults to `'text'`
	type?: 'text' | 'token';
	// from withSpokenMessages
	debouncedSpeak?: ( message: string ) => void;
}
