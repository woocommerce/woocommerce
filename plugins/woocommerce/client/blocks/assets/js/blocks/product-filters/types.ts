/**
 * External dependencies
 */
import type { BlockEditProps } from '@wordpress/blocks';

export type BlockAttributes = {
	productId?: string;
	isPreview: boolean;
};

export type EditProps = BlockEditProps< BlockAttributes >;

export type FilterOptionItem = {
	label: string;
	value: string;
	selected?: boolean;
	count: number;
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
