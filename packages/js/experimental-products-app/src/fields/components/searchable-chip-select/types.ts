/**
 * External dependencies
 */
import type {
	ComboboxChipProps,
	ComboboxCollectionProps,
	ComboboxEmptyProps,
	ComboboxInputProps,
	ComboboxItemProps,
	ComboboxRootProps,
} from '@base-ui/react/combobox';
import type { ReactNode } from 'react';

export type SearchableChipSelectItem = {
	label: string;
	value: string;
	disabled?: boolean;
};

export type Item = SearchableChipSelectItem;

export type SearchableChipSelectProps = Omit<
	ComboboxRootProps< SearchableChipSelectItem, true >,
	'children' | 'items' | 'multiple'
> &
	Partial<
		Pick<
			ComboboxInputProps,
			'aria-label' | 'aria-labelledby' | 'aria-describedby'
		>
	> & {
		/**
		 * The array of option items.
		 */
		items?: SearchableChipSelectItem[];
		/**
		 * A render function for custom rendering the list of matching items.
		 */
		children?: ComboboxCollectionProps[ 'children' ];
		/**
		 * The item that triggers the creation of a new item.
		 */
		creatableItem?: SearchableChipSelectItem;
		/**
		 * A render function for custom rendering the selected chips.
		 */
		chipsContent?: ( value: SearchableChipSelectItem[] ) => ReactNode;
		/**
		 * Chip content to show when there are no selected values.
		 */
		placeholderChip?: ReactNode;
		/**
		 * The custom content to use instead of the default empty state.
		 */
		emptyContent?: ComboboxEmptyProps[ 'children' ];
		/**
		 * The placeholder text to use for the search input.
		 */
		searchPlaceholder?: ComboboxInputProps[ 'placeholder' ];
		/**
		 * Whether to show the clear button to remove all selected items.
		 *
		 * @default true
		 */
		showClearButton?: boolean;
		/**
		 * The aria-label for the clear button.
		 */
		clearButtonLabel?: string;
	};

export type SearchableChipSelectControlProps = SearchableChipSelectProps & {
	className?: string;
	label: ReactNode;
	description?: ReactNode;
};

export type SearchableChipSelectItemProps = ComboboxItemProps & {
	children?: ReactNode;
};

export type SearchableChipSelectChipWithRemoveProps = Omit<
	ComboboxChipProps,
	'prefix'
> & {
	children?: ReactNode;
	/**
	 * Circular element to render before the chip content.
	 */
	prefix?: ReactNode;
};
