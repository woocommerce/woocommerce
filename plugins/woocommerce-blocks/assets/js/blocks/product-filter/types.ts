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
	providerNameSlug: string;
};

export type EditProps = BlockEditProps< BlockAttributes >;
