/**
 * External dependencies
 */
import type { ReactNode } from 'react';

export type SelectableItem< T = unknown > = (
	| { label: string; ariaLabel?: string }
	| { label: ReactNode; ariaLabel: string }
 ) & {
	id: string;
	value: string;
	selected?: boolean;
	disabled?: boolean;
	type?: string;
} & T;

/**
 * Runtime shape of items yielded by the parent's `state.selectableItems`
 * getter. Parent derives `selected` from SSOT and `index` from position
 * so inner blocks can branch on it (e.g. show-more visibility in
 * `checkbox-list`).
 */
export type DerivedSelectableItem< T = unknown > = SelectableItem< T > & {
	index: number;
};

export interface SelectableItemsContext< T = unknown > {
	items: SelectableItem< T >[];
	selectionMode: 'single' | 'multiple';
	storeNamespace: string;
	groupLabel?: string;
	isLoading?: boolean;
	/**
	 * Domain discriminator that inner blocks can use to vary presentation
	 * (e.g. `'rating'` unlocks star rendering in `checkbox-list`). Values
	 * are parent-defined strings; unknown values fall back to text.
	 */
	filterType?: string;
}

export type SelectableItemsBlockContext< T = unknown > = {
	'woocommerce/selectableItems': SelectableItemsContext< T >;
};

/**
 * Contract every parent store referenced by `storeNamespace` MUST satisfy.
 * Use with `satisfies` for compile-time enforcement:
 *
 *   productFiltersStore satisfies SelectableItemsParentStore;
 *
 * `state.selectableItems` returns items with `selected` + `index` derived.
 * `actions.toggle` reads `getContext().item` (set by `data-wp-each` in items region).
 */
export interface SelectableItemsParentStore< T = unknown > {
	state: {
		selectableItems: readonly DerivedSelectableItem< T >[];
	};
	actions: {
		/** Toggles selection for the current `getContext().item`. */
		toggle: () => void;
	};
}
