/**
 * External dependencies
 */
import {
	ProductData,
	VariationData,
} from '@woocommerce/stores/woocommerce/cart';

export type QuantitySelectorStyleProps = 'input' | 'stepper';

export interface Attributes {
	className?: string;
}

export type ProductDataWithId = ProductData & {
	id: number;
	min: number;
	max: number;
	step: number;
	is_in_stock: boolean;
	sold_individually: boolean;
};

export type VariationDataWithId = VariationData & {
	id: number;
	min: number;
	max: number;
	step: number;
	is_in_stock: boolean;
	sold_individually: boolean;
	type: 'variation';
};
