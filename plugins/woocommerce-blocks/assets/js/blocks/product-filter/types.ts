/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { BLOCK_NAME_MAP } from './constants';

export type FilterType = keyof typeof BLOCK_NAME_MAP;

export type BlockAttributes = {
	filterType: FilterType;
	heading: string;
};

export type EditProps = BlockEditProps< BlockAttributes >;

export type FilterOption = {
	id: string;
	label: string;
	value: unknown;
	count: number;
	attrs?: unknown;
	checked?: boolean;
};
