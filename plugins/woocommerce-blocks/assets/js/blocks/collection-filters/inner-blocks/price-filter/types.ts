/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

export type BlockAttributes = {
	showInputFields: boolean;
	inlineInput: boolean;
};

export type EditProps = BlockEditProps< BlockAttributes >;

export type PriceFilterState = {
	minPrice?: number;
	maxPrice?: number;
	minRange?: number;
	maxRange?: number;
	rangeStyle: string;
	formattedMinPrice: string;
	formattedMaxPrice: string;
};
