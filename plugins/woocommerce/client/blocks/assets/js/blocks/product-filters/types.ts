/**
 * External dependencies
 */
import type { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import type { SelectableItem } from '../../types/type-defs/selectable-items';

// ----------------------------------------
// Filter-specific item fields
// ----------------------------------------
export type FilterItemFields = {
	count?: number;
	termId?: number;
	parent?: number;
	depth?: number;
	menuOrder?: number;
	attributeQueryType?: 'and' | 'or';
	color?: string;
};

export type FilterOptionItem = SelectableItem< FilterItemFields >;

// ----------------------------------------
// Parent store context + active-filter shape
// ----------------------------------------
export type ActiveFilterItem = {
	type: string;
	value: string;
	attributeQueryType?: 'and' | 'or';
	activeLabel: string;
};

export type ProductFiltersContext = {
	isOverlayOpened: boolean;
	params: Record< string, string >;
	activeFilters: ActiveFilterItem[];
	items?: FilterOptionItem[];
	item: FilterOptionItem;
	activeLabelTemplate: string;
	filterType: string;
};

// ----------------------------------------
// Block props
// ----------------------------------------
export type BlockAttributes = {
	productId?: string;
	isPreview: boolean;
};

export type EditProps = BlockEditProps< BlockAttributes >;
