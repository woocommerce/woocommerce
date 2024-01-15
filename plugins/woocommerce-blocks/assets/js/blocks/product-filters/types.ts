/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

export type FilterType =
	| 'price-filter'
	| 'attribute-filter'
	| 'rating-filter'
	| 'active-filters'
	| 'stock-filter'
	| 'product-filters';

export type BlockAttributes = {
	filterType: FilterType;
};

export type EditProps = BlockEditProps< BlockAttributes >;
