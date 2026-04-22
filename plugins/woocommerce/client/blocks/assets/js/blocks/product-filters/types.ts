/**
 * External dependencies
 */
import type { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import type {
	SelectableItem,
	SelectableItemsContext,
} from '../../types/type-defs/selectable-items';
import type { RangeInputContext } from '../../types/type-defs/range-input';
import type { ActiveFiltersContext } from '../../types/type-defs/active-filters';

// ----------------------------------------
// Filter-specific item fields
// ----------------------------------------
export type FilterItemFields = {
	count: number;
	termId?: number;
	parent?: number;
	depth?: number;
};

export type FilterOptionItem = SelectableItem< FilterItemFields >;

// ----------------------------------------
// Filter block context (parent blocks use this)
// ----------------------------------------
export type FilterBlockContext = {
	'woocommerce/selectableItems'?: SelectableItemsContext< FilterItemFields >;
	'woocommerce/rangeInput'?: RangeInputContext;
	'woocommerce/activeFilters'?: ActiveFiltersContext;
};

// ----------------------------------------
// Block props
// ----------------------------------------
export type BlockAttributes = {
	productId?: string;
	isPreview: boolean;
};

export type EditProps = BlockEditProps< BlockAttributes >;

// ----------------------------------------
// Editor color picker
// ----------------------------------------
export type Color = {
	slug?: string;
	class?: string;
	name?: string;
	color: string;
};
