/**
 * External dependencies
 */
import type { CurrencyConfig } from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import type { AutoCompleter } from '../search/autocompleters';

/**
 * Parsed URL query. `allowMultiple` filters serialize as nested arrays
 * (`attribute_is[0][0]=1`), so values are not always plain strings.
 */
export type Query = Record<
	string,
	string | string[] | string[][] | undefined
>;

export type FilterRule = {
	value: string;
	label: string;
};

export type FilterOption = {
	value: string;
	label: string;
};

export type FilterLabels = {
	add: string;
	remove?: string;
	rule?: string;
	title: string;
	filter?: string;
	placeholder?: string;
};

/**
 * A label resolved for a selected search value, as returned by `input.getLabels`.
 */
export type SearchLabel = {
	id?: string | number;
	key?: string | number;
	label: string;
};

export type FilterInput = {
	component: string;
	type?: string;
	options?: FilterOption[];
	defaultOption?: string;
	getOptions?: () => Promise< FilterOption[] >;
	getLabels?: ( value: string, query?: Query ) => Promise< SearchLabel[] >;
	autocompleter?: AutoCompleter;
};

export type FilterConfig = {
	labels: FilterLabels;
	rules?: FilterRule[];
	input: FilterInput;
	allowMultiple?: boolean;
};

export type AdvancedFilterConfig = {
	title: string;
	filters: Record< string, FilterConfig >;
};

/**
 * Range filters hold `[ start, end ]` while either end may still be unset.
 */
export type ActiveFilterValue =
	| string
	| number
	| Array< string | number | null | undefined >
	| null;

export type ActiveFilter = {
	key: string;
	rule?: string;
	value?: ActiveFilterValue;
	instance?: number;
};

export type FilterChange = {
	property: 'rule' | 'value';
	value: ActiveFilterValue | undefined;
	shouldResetValue?: boolean;
};

export type OnFilterChange = ( change: FilterChange ) => void;

export type AdvancedFilterAction =
	| 'add'
	| 'remove'
	| 'match'
	| 'filter'
	| 'clear_all';

/**
 * `getCurrencyConfig()` returns `symbolPosition` as a plain string, so this stays
 * wider than `CurrencyProps` to accept what the site actually passes.
 */
export type Currency = Omit< CurrencyConfig, 'symbolPosition' > & {
	symbol: string;
	symbolPosition: string;
};

/**
 * Props shared by every single-filter component rendered by `AdvancedFilterItem`.
 */
export type FilterComponentProps = {
	className?: string;
	config: FilterConfig;
	filter: ActiveFilter;
	isEnglish?: boolean;
	onFilterChange: OnFilterChange;
	query?: Query;
};
