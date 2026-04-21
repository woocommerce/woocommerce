/**
 * External dependencies
 */
import type { ReactNode } from 'react';

export interface SelectableItemsContext {
	items: SelectableItem[];
	selectionMode: 'single' | 'multiple';
	selectAction: string;
	storeNamespace: string;
	groupLabel?: string;
	showCounts?: boolean;
}

export type SelectableItem = (
	| { label: string; ariaLabel?: string }
	| { label: ReactNode; ariaLabel: string }
 ) & {
	value: string;
	selected?: boolean;
	count?: number;
	disabled?: boolean;
	type?: string;
	color?: string;
	image?: string;
	id?: number;
	parent?: number;
	depth?: number;
	menuOrder?: number;
	[ key: string ]: unknown;
};
