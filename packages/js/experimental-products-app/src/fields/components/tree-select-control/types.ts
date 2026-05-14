/**
 * External dependencies
 */
import type { ReactNode } from 'react';

/**
 * Base option structure for tree select
 */
export interface BaseOption {
	/** The label for the option */
	label: string;
	/** The value for the option */
	value: string;
	/** Optional unique key for the Option. It will fallback to the value property if not defined */
	key?: string;
	/** The children Option objects */
	children?: Option[];
}

export type Option = BaseOption;

/**
 * Internal option structure with computed properties for rendering
 */
export interface InnerOption extends Omit< BaseOption, 'label' | 'children' > {
	/** The label string or label with highlighted react element for the option */
	label: string | ReactNode;
	/** The children options. The options are filtered if in searching */
	children?: InnerOption[];
	/** Whether this option has children */
	readonly hasChildren: boolean;
	/** All leaf options that are flattened under this option. The options are filtered if in searching */
	readonly leaves: InnerOption[];
	/** Whether this option is checked */
	readonly checked: boolean;
	/** Whether this option is partially checked */
	readonly partialChecked: boolean;
	/** Whether this option is expanded */
	readonly expanded: boolean;
	/** The parent value of the current option */
	parent?: string;
	/** Whether this option should be visible based on search */
	readonly isVisible: boolean;
	/** Whether this option is a searched result */
	readonly isSearchResult: boolean;
	/** Whether this option matches the current filter */
	filterMatch?: boolean;
}

/**
 * Type for the property descriptors used with Object.defineProperties
 * to add computed properties to InnerOption objects
 */
export type OptionPropertyDescriptors = {
	hasChildren: PropertyDescriptor & {
		get: ( this: InnerOption ) => boolean;
	};
	leaves: PropertyDescriptor & {
		get: ( this: InnerOption ) => InnerOption[];
	};
	checked: PropertyDescriptor & {
		get: ( this: InnerOption ) => boolean;
	};
	partialChecked: PropertyDescriptor & {
		get: ( this: InnerOption ) => boolean;
	};
	isVisible: PropertyDescriptor & {
		get: ( this: InnerOption ) => boolean;
	};
	isSearchResult: PropertyDescriptor & {
		get: ( this: InnerOption ) => boolean;
	};
	expanded: PropertyDescriptor & {
		get: ( this: InnerOption ) => boolean;
	};
};

/**
 * Tag item interface
 */
export interface TagItem {
	/** Unique identifier for the tag */
	id: string;
	/** Display label for the tag */
	label?: string;
}

/**
 * TreeSelectControl component props
 */
export interface TreeSelectControlProps {
	/** The id of the TreeSelect */
	id?: string;
	/** Label for the component */
	label?: string;
	/** Help text displayed under the control */
	description?: string;
	/** Label for the Select All root element. False for disable. */
	selectAllLabel?: string | false;
	/** Placeholder for the search control input */
	placeholder?: string;
	/** The CSS class to apply. */
	className?: string;
	/** Whether the component should ignore user interaction. */
	disabled?: boolean;
	/** Includes parent with selection */
	includeParent?: boolean;
	/** Considers parent as a single item (default: false) */
	individuallySelectParent?: boolean;
	/** Will always show placeholder (default: false) */
	alwaysShowPlaceholder?: boolean;
	/** Options to show in the component */
	options?: Option[];
	/** Selected values */
	value?: string[];
	/** The maximum number of tags to show. Undefined, 0 or less than 0 evaluates to "Show All" */
	maxVisibleTags?: number;
	/** Callback fired when the value changes */
	onValueChange?: ( value: string[] ) => void;
	/** Callback when the visibility of the dropdown options is changed */
	onDropdownVisibilityChange?: ( visible: boolean ) => void;
	/** Callback when the selector changes */
	onInputChange?: ( value: string ) => void;
	/** Minimum input length to filter results by */
	minFilterQueryLength?: number;
	/** Clear input on select (default: true) */
	clearOnSelect?: boolean;
}

/**
 * Options component props
 */
export interface OptionsProps {
	/** List of options to be rendered */
	options?: InnerOption[];
	/** Parent option */
	parent?: InnerOption | null;
	/** The current level of depth in the tree */
	level?: number;
	/** Callback when an option changes */
	onChange?: (
		checked: boolean,
		option: InnerOption,
		parent: InnerOption | null
	) => void;
	/** Callback when an expander is clicked */
	onExpanderClick?: ( event: React.MouseEvent ) => void;
	/** Callback when requesting an expander to be toggled */
	onToggleExpanded?: ( option: InnerOption ) => void;
}
