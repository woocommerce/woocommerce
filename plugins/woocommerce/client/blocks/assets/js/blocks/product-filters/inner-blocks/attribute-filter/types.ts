/**
 * External dependencies
 */
import type { BlockEditProps } from '@wordpress/blocks';
import type { AttributeCount } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { sortOrders } from './constants';

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
