/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

export type BlockAttributes = {
	queryParam: Record< string, unknown >;
	attributeId: number;
	showCounts: boolean;
	queryType: string;
	displayStyle: string;
	selectType: string;
	isPreview: boolean;
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
