/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';
import { FilterOption } from '../../types';

/**
 * Internal dependencies
 */

export type BlockAttributes = {
	showCounts: boolean;
};

export type EditProps = BlockEditProps< BlockAttributes > & {
	context: {
		filterOptions: FilterOption[];
	};
};
