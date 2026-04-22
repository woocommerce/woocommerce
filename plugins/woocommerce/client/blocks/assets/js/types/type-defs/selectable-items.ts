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
	selectAction: string;
	storeNamespace: string;
	groupLabel?: string;
	dynamicItems?: boolean;
	isLoading?: boolean;
}

export type SelectableItemsBlockContext< T = unknown > = {
	'woocommerce/selectableItems': SelectableItemsContext< T >;
};
