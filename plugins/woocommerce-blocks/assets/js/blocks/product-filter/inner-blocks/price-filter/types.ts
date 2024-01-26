/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';
import { HTMLElementEvent } from '@woocommerce/types';

export type BlockAttributes = {
	showInputFields: boolean;
	inlineInput: boolean;
};

export type EditProps = BlockEditProps< BlockAttributes >;

export type PriceFilterState = {
	rangeStyle: () => string;
	formattedMinPrice: () => string;
	formattedMaxPrice: () => string;
};

export type PriceFilterContext = {
	minPrice: number;
	maxPrice: number;
	minRange: number;
	maxRange: number;
};

export type FilterComponentProps = BlockEditProps< BlockAttributes > & {
	collectionData: Partial< PriceFilterState >;
};

export type PriceFilterStore = {
	state: PriceFilterState;
	actions: {
		updateProducts: ( event: HTMLElementEvent< HTMLInputElement > ) => void;
		reset: () => void;
	};
};
