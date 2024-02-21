/**
 * External dependencies
 */
import { AttributeTerm } from '@woocommerce/types';
import { BlockEditProps } from '@wordpress/blocks';
import { FilterOption } from '../../types';

/**
 * Internal dependencies
 */

export type BlockAttributes = {
	termColors: Record< number, string >;
	displayStyle: string;
};

export type EditProps = BlockEditProps< BlockAttributes > & {
	context: {
		attributeId: number;
		filterOptions: FilterOption[];
		attributeTerms: AttributeTerm[];
	};
};
