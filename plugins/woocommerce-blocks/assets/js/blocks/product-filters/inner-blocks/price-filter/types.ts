/**
 * External dependencies
 */
import { HTMLElementEvent } from '@woocommerce/types';

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

export type PriceFilterStore = {
	state: PriceFilterState;
	actions: {
		updateProducts: ( event: HTMLElementEvent< HTMLInputElement > ) => void;
		selectInputContent: () => void;
		reset: () => void;
		updateRange: ( event: HTMLElementEvent< HTMLInputElement > ) => void;
	};
};
