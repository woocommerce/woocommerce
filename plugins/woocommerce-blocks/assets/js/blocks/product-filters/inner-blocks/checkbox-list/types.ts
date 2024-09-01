/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { FilterDataContext } from '../../types';

export type BlockAttributes = {
	className: string;
};

export type EditProps = BlockEditProps< BlockAttributes > & {
	context: FilterDataContext;
};
