/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

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
	clearButton: boolean;
};

export interface EditProps extends BlockEditProps< BlockAttributes > {
	debouncedSpeak: ( label: string ) => void;
}

type AttributeCount = {
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
