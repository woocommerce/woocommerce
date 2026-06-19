/**
 * External dependencies
 */
import type { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { sortOrders } from './constants';

export type BlockAttributes = {
	attributeId?: number;
	showCounts?: boolean;
	queryType?: 'or' | 'and';
	displayStyle?: string;
	selectType?: string;
	isPreview?: boolean;
	sortOrder?: keyof typeof sortOrders;
	hideEmpty?: boolean;
};

export const DEFAULT_SORT_ORDER = 'count-desc' as const;
export const DEFAULT_QUERY_TYPE = 'or' as const;
export const DEFAULT_DISPLAY_STYLE = 'woocommerce/product-filter-checkbox-list';
export const DEFAULT_SELECT_TYPE = 'multiple';

export interface EditProps extends BlockEditProps< BlockAttributes > {
	debouncedSpeak: ( label: string ) => void;
}

export type AttributeSetting = {
	attribute_id: string;
	attribute_name: string;
	attribute_label: string;
	attribute_orderby: 'menu_order' | 'name' | 'name_num' | 'id';
	attribute_public: 0 | 1;
	attribute_type: string;
};

export type AttributeObject = {
	id: number;
	name: string;
	taxonomy: string;
	label: string;
};

export type AttributeTerm = {
	attr_slug: string;
	count: number;
	description: string;
	id: number;
	name: string;
	parent: number;
	slug: string;
	// eslint-disable-next-line @typescript-eslint/naming-convention
	__experimentalVisual?: unknown;
};

export type AttributeCount = {
	term: number;
	count: number;
};

export function isAttributeCounts(
	target: unknown
): target is AttributeCount[] {
	return (
		Array.isArray( target ) &&
		target.every( ( item ) => 'term' in item && 'count' in item )
	);
}
