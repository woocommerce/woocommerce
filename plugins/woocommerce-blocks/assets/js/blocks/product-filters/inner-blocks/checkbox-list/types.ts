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

type CheckboxListItem = {
	value: string;
	label: string;
};

export type EditProps = BlockEditProps< BlockAttributes > & {
	context: FilterDataContext< CheckboxListItem >;
};
