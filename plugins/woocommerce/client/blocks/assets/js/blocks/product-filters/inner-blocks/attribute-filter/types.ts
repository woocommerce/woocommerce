/**
 * External dependencies
 */
import type { BlockEditProps } from '@wordpress/blocks';
import type { AttributeCount } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { sortOrders } from './constants';
import metadata from './block.json';

export type BlockAttributes = {
	attributeId: number;
	showCounts: boolean;
	queryType: 'or' | 'and';
	displayStyle: string;
	selectType: string;
	isPreview: boolean;
	sortOrder: keyof typeof sortOrders;
	hideEmpty: boolean;
};

export const DEFAULT_SORT_ORDER = metadata.attributes.sortOrder
	.default as BlockAttributes[ 'sortOrder' ];
export const DEFAULT_QUERY_TYPE = metadata.attributes.queryType
	.default as BlockAttributes[ 'queryType' ];

export interface EditProps extends BlockEditProps< BlockAttributes > {
	debouncedSpeak: ( label: string ) => void;
}

export function isAttributeCounts(
	target: unknown
): target is AttributeCount[] {
	return (
		Array.isArray( target ) &&
		target.every( ( item ) => 'term' in item && 'count' in item )
	);
}
