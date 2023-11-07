/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';
import { HTMLElementEvent } from '@woocommerce/types';

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
	minPrice: number;
	maxPrice: number;
	minRange: number;
	maxRange: number;
	formattedMinPrice: string;
	formattedMaxPrice: string;
};

export type StateProps = {
	state: {
		filters: PriceFilterState;
	};
};

export interface ActionProps extends StateProps {
	event: HTMLElementEvent< HTMLInputElement >;
}

export type FilterComponentProps = BlockEditProps< BlockAttributes > & {
	collectionData: Partial< PriceFilterState >;
};
