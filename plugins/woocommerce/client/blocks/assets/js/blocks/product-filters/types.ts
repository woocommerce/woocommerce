/**
 * External dependencies
 */
import type { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import type { SelectableItem } from '../../types/type-defs/selectable-items';
import type { VisualAttributeTerm } from '../../base/utils/visual-attribute-terms';

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
	visual?: VisualAttributeTerm;
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
	// Set when Product Filters is a descendant of Product Collection. Null
	// signals the frontend to fall back to the global interactivity config
	// (sibling-block layout).
	forcePageReload?: boolean | null;
};

// ----------------------------------------
// Block props
// ----------------------------------------
export type BlockAttributes = {
	productId?: string;
	isPreview: boolean;
	showFilterDrawer?: boolean;
};

export type EditProps = BlockEditProps< BlockAttributes >;
