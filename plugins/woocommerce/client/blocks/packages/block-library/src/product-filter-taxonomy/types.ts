/**
 * External dependencies
 */
import type { BlockEditProps } from '@wordpress/blocks';

export type BlockAttributes = {
	taxonomy?: string;
	showCounts?: boolean;
	displayStyle?: string;
	isPreview?: boolean;
	sortOrder?: string;
	hideEmpty?: boolean;
};

export type EditProps = BlockEditProps< BlockAttributes >;

export type TaxonomyItem = {
	name: string;
	label: string;
};

export const DEFAULT_TAXONOMY = 'product_cat';
export const DEFAULT_DISPLAY_STYLE = 'woocommerce/product-filter-checkbox-list';
export const DEFAULT_SORT_ORDER = 'count-desc';
