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
	hidden?: boolean;
	type?: string;
} & T;

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
	// eslint-disable-next-line @typescript-eslint/naming-convention
	'woocommerce/selectableItems': SelectableItemsContext< T >;
};

/**
 * Contract every parent store referenced by `storeNamespace` MUST satisfy.
 *
 * Two consumption patterns:
 * - **Direct**: inner block iterates `state.selectableItems` under the parent
 *   namespace via nested `data-wp-interactive`; `data-wp-each` sets
 *   `context.item` automatically; `toggle()` reads it from context.
 * - **Mirror**: inner block copies parent items into its own store, iterates
 *   under its own namespace, and calls `toggle( item )` explicitly.
 *
 * `toggle` accepts an optional item so both patterns work.
 */
export interface SelectableItemsParentStore< T = unknown > {
	state: {
		selectableItems: readonly SelectableItem< T >[];
	};
	actions: {
		toggle: ( item?: SelectableItem< T > ) => void;
	};
}
