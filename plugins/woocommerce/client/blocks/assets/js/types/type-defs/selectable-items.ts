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
	dynamicItems?: boolean;
	isLoading?: boolean;
}

export type SelectableItemsBlockContext< T = unknown > = {
	'woocommerce/selectableItems': SelectableItemsContext< T >;
};

/**
 * Contract every parent store referenced by `storeNamespace` MUST satisfy.
 * Use with `satisfies` to get compile-time enforcement:
 *
 *   productFiltersStore satisfies SelectableItemsParentStore;
 *
 * The `items` getter must return objects that at minimum satisfy `SelectableItem`.
 * Extra domain fields on items are fine — the contract only enforces the shared base.
 */
export interface SelectableItemsParentStore {
	state: {
		isSelected: boolean;
	};
	actions: {
		toggle: () => void;
	};
}
