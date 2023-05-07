/**
 * External dependencies
 */
import { ReactNode, ReactElement } from 'react';

// Options may be of any type or shape.
// eslint-disable-next-line @typescript-eslint/no-explicit-any
export type CompleterOption = any;
export type FnGetOptions = (
	query?: string
) => CompleterOption[] | Promise< CompleterOption[] >;
export type OptionCompletionValue = string | ReactElement | object;
export type OptionCompletion = {
	/**
	 * The action declares what should be done with the value.
	 * There are currently two supported actions:
	 * 1. "insert-at-caret" - Insert the value into the text (the default completion action).
	 * 2. "replace" - Replace the current block with the block specified in the value property.
	 */
	action: 'insert-at-caret' | 'replace';
	// The completion value.
	value: OptionCompletionValue;
};
export type FnGetOptionCompletion = (
	// The value of the completer option.
	value: CompleterOption
) => OptionCompletion | OptionCompletionValue;

export type AutoCompleter = {
	/* The name of the completer. Useful for identifying a specific completer to be overridden via extensibility hooks. */
	name: string;
	/* The raw options for completion. May be an array, a function that returns an array, or a function that returns a promise for an array. */
	options: CompleterOption[] | FnGetOptions;
	/* A function that returns a key to be used for the option. */
	getOptionIdentifier: ( option: CompleterOption ) => string | number;
	/* A function that returns the label for a given option. A label may be a string or a mixed array of strings, elements, and components. */
	getOptionLabel: ( option: CompleterOption, query: string ) => ReactNode;
	/* A function that takes an option and responds with how the option should be completed. By default, the result is a value to be inserted in the text. However, a completer may explicitly declare how a completion should be treated by returning an object with action and value properties. */
	getOptionCompletion: FnGetOptionCompletion;
	/* A function that returns the keywords for the specified option. */
	getOptionKeywords: ( option: CompleterOption ) => string[];
	/* A function that returns whether or not the specified option should be disabled. Disabled options cannot be selected. */
	isOptionDisabled?: ( option: CompleterOption ) => boolean;
	/* A function that takes a Range before and a Range after the autocomplete trigger and query text and returns a boolean indicating whether the completer should be considered for that context. */
	allowContext?: ( before: string, after: string ) => boolean;
	/* A function that returns options for the specified query. This is useful for filtering options based on the query. */
	getFreeTextOptions?: ( query: string ) => [
		{
			key: string;
			label: JSX.Element;
			value: unknown;
		}
	];
	/* A function to add regex expression to the filter the results, passed the search query. */
	getSearchExpression?: ( query: string ) => string;
	/* A class name to apply to the autocompletion popup menu. */
	className?: string;
	/* Whether to apply debouncing for the autocompleter. Set to true to enable debouncing. */
	isDebounced?: boolean;
	/* The input type for the search box control. */
	inputType?: 'text' | 'search' | 'number' | 'email' | 'tel' | 'url';
};
