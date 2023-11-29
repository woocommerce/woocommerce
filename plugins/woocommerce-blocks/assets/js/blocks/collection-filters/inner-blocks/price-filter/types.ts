/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

export type BlockAttributes = {
	showInputFields: boolean;
	inlineInput: boolean;
};

export interface EditProps extends BlockEditProps< BlockAttributes > {
	context: {
		collectionData: unknown[];
	};
}

export type PriceFilterState = {
	minPrice?: number;
	maxPrice?: number;
	minRange?: number;
	maxRange?: number;
	rangeStyle: string;
	formattedMinPrice: string;
	formattedMaxPrice: string;
};

export type FilterComponentProps = BlockEditProps< BlockAttributes > & {
	collectionData: Partial< PriceFilterState >;
};
