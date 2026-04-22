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
	count: number;
	termId?: number;
	parent?: number;
	depth?: number;
	menuOrder?: number;
};

export type FilterOptionItem = SelectableItem< FilterItemFields >;

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
