/**
 * External dependencies
 */
import type { ReactNode } from 'react';

export type SearchListItemType = {
	id: string | number;
	name: string;
	value: string;
	parent: number;
	count: number;
	children: SearchListItemsType;
	breadcrumbs: string[];
};

export type SearchListItemsType = SearchListItemType[] | [  ];

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

export interface renderItemArgs {
	// Additional CSS classes.
	className?: string;
	// Current item to display.
	item: SearchListItemType;
	// Whether this item is selected.
	isSelected: boolean;
	// Callback for selecting the item.
	onSelect: ( item: SearchListItemType ) => () => void;
	// Whether this should only display a single item (controls radio vs checkbox icon).
	isSingle: boolean;
	// Search string, used to highlight the substring in the item name.
	search: string;
	// Depth, non-zero if the list is hierarchical.
	depth: number;
	// Unique id of the parent control.
	controlId: string | number;
	// Label to display in the count bubble. Takes preference over `item.count`.
	countLabel?: ReactNode;
	/**
	 * Name of the inputs. Used to group input controls together. See:
	 * https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input#attr-name
	 * If not provided, a default name will be generated using the controlId.
	 */
	name?: string;
}

export interface SearchListControlProps {
	// Additional CSS classes.
	className: string;
	// Whether it should be displayed in a compact way, so it occupies less space.
	isCompact: boolean;
	// Whether the list of items is hierarchical or not. If true, each list item is expected to have a parent property.
	isHierarchical: boolean;
	// Whether the list of items is still loading.
	isLoading: boolean;
	//Restrict selections to one item.
	isSingle: boolean;
	// A complete list of item objects, each with id, name properties. This is displayed as a clickable/keyboard-able list, and possibly filtered by the search term (searches name).
	list: SearchListItemsType;
	// Messages displayed or read to the user. Configure these to reflect your object type.
	messages?: Partial< SearchListMessages >;
	// Callback fired when selected items change, whether added, cleared, or removed. Passed an array of item objects (as passed in via props.list).
	onChange: ( search: SearchListItemsType ) => void;
	// Callback fired when the search field is used.
	onSearch?: ( search: string ) => void;
	// Callback to render each item in the selection list, allows any custom object-type rendering.
	renderItem: ( args: renderItemArgs ) => JSX.Element;
	// The list of currently selected items.
	selected: SearchListItemsType;
	// from withSpokenMessages
	debouncedSpeak: ( message: string ) => void;
}
