/**
 * External dependencies
 */
import type { ReactNode } from 'react';

export type FilterItemFields = {
	count?: number;
	termId?: number;
	parent?: number;
	depth?: number;
	menuOrder?: number;
	attributeQueryType?: 'and' | 'or';
};

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

export type FilterOptionItem = SelectableItem< FilterItemFields >;

export interface SelectableItemsContext< T = unknown > {
	items: SelectableItem< T >[];
	selectionMode: 'single' | 'multiple';
	storeNamespace: string;
	groupLabel?: string;
	isLoading?: boolean;
	filterType?: string;
}

export type SelectableItemsBlockContext< T = unknown > = {
	// eslint-disable-next-line @typescript-eslint/naming-convention
	'woocommerce/selectableItems': SelectableItemsContext< T >;
};

export interface RemovableItem {
	id: string;
	type: string;
	value: string;
	label: string;
}

export interface RemovableItemsContext {
	items: RemovableItem[];
	storeNamespace: string;
}

export type RemovableItemsBlockContext = {
	// eslint-disable-next-line @typescript-eslint/naming-convention
	'woocommerce/removableItems': RemovableItemsContext;
};

export interface RangeInputContext {
	min: number;
	max: number;
	currentMin: number;
	currentMax: number;
	step?: number;
	storeNamespace?: string;
	isLoading?: boolean;
}

export type RangeInputBlockContext = {
	// eslint-disable-next-line @typescript-eslint/naming-convention
	'woocommerce/rangeInput': RangeInputContext;
};
