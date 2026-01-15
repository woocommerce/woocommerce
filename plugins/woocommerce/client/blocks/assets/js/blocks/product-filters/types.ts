/**
 * External dependencies
 */
import type { BlockEditProps } from '@wordpress/blocks';
import type { ReactNode } from 'react';

export type BlockAttributes = {
	productId?: string;
	isPreview: boolean;
};

export type EditProps = BlockEditProps< BlockAttributes >;

export type FilterOptionItem = (
	| {
			label: string;
			ariaLabel?: string;
	  }
	| {
			label: ReactNode;
			ariaLabel: string;
	  }
 ) & {
	value: string;
	selected?: boolean;
	count: number;
	id?: number;
	parent?: number;
	depth?: number;
};

export type FilterBlockContext = {
	filterData: {
		isLoading: boolean;
		items?: FilterOptionItem[];
		price?: {
			minPrice: number;
			minRange: number;
			maxPrice: number;
			maxRange: number;
		};
		showCounts?: boolean;
	};
};

export type Color = {
	slug?: string;
	class?: string;
	name?: string;
	color: string;
};
