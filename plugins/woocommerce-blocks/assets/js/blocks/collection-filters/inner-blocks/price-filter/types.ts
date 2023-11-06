/**
 * External dependencies
 */
import { HTMLElementEvent } from '@woocommerce/types';
import { BlockEditProps } from '@wordpress/blocks';
import { ProductCollectionQuery } from '@woocommerce/blocks/product-collection/types';

type PriceFilterState = {
	minPrice: number;
	maxPrice: number;
	minRange: number;
	maxRange: number;
	rangeStyle: string;
	isMinActive: boolean;
	isMaxActive: boolean;
	formattedMinPrice: string;
	formattedMaxPrice: string;
};

export type StateProps = {
	state: {
		filters: PriceFilterState;
	};
};

export type ActionProps = StateProps & {
	event: HTMLElementEvent< HTMLInputElement >;
};

export type BlockAttributes = {
	showInputFields: boolean;
	inlineInput: boolean;
};

export interface EditProps extends BlockEditProps< BlockAttributes > {
	context: {
		query: ProductCollectionQuery;
	};
}

export type FilterComponentProps = BlockEditProps< BlockAttributes > & {
	collectionData: Partial< PriceFilterState >;
};
