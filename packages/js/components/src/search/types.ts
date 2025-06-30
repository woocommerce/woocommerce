/**
 * Internal dependencies
 */
import type { Option } from '../select-control/types';
import type { AutoCompleter, OptionCompletionValue } from './autocompleters';

export type SearchType =
	| 'attributes'
	| 'categories'
	| 'countries'
	| 'coupons'
	| 'customers'
	| 'downloadIps'
	| 'emails'
	| 'orders'
	| 'products'
	| 'taxes'
	| 'usernames'
	| 'variableProducts'
	| 'variations'
	| 'custom';

export type SearchState = {
	options: Option[];
};

export type SearchProps = {
	/**
	 * Render additional options in the autocompleter to allow free text entering depending on the type.
	 */
	allowFreeTextSearch?: boolean;
	/**
	 * Class name applied to parent div.
	 */
	className?: string;
	/**
	 * Function called when selected results change, passed result list.
	 */
	onChange?( value: Option | OptionCompletionValue[] ): unknown;
	/**
	 * The object type to be used in searching.
	 */
	type: SearchType;
	/**
	 * The custom autocompleter to be used in searching when type is 'custom'
	 */
	autocompleter?: AutoCompleter;
	/**
	 * A placeholder for the search input.
	 */
	placeholder?: string;
	/**
	 * An array of objects describing selected values or optionally a string for a single value.
	 * If the label of the selected value is omitted, the Tag of that value will not
	 * be rendered inside the search box.
	 */
	selected?:
		| string
		| Array< {
				key: string;
				label: string;
		  } >;
	/**
	 * Render tags inside input, otherwise render below input.
	 */
	inlineTags?: boolean;
	/**
	 * Render a 'Clear' button next to the input box to remove its contents.
	 */
	showClearButton?: boolean;
	/**
	 * Render results list positioned statically instead of absolutely.
	 */
	staticResults?: boolean;
	/**
	 * Whether the control is disabled or not.
	 */
	disabled?: boolean;
	/**
	 * Allow multiple option selections.
	 */
	multiple?: boolean;
};
