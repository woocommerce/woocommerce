/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { FilterBlockContext } from '../../types';

export type BlockAttributes = {
	showInputFields: boolean;
	inlineInput: boolean;
};

export interface EditProps extends BlockEditProps< BlockAttributes > {
	context: FilterBlockContext;
}
