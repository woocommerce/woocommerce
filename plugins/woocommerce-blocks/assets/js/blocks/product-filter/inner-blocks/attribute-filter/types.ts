/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { sortOrders } from './constants';
import {
	EditProps as CheckboxListEditProps,
	Attributes as CheckboxListAttributes,
} from './components/checkbox-list-editor';

export interface BlockAttributes
	extends Record< string, unknown >,
		CheckboxListAttributes {
	attributeId: number;
	showCounts: boolean;
	queryType: 'or' | 'and';
	displayStyle: string;
	selectType: string;
	isPreview: boolean;
	sortOrder: keyof typeof sortOrders;
	hideEmpty: boolean;
	clearButton: boolean;
}

export interface EditProps
	extends BlockEditProps< BlockAttributes >,
		CheckboxListEditProps {
	debouncedSpeak: ( label: string ) => void;
	style: Record< string, string >;
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
