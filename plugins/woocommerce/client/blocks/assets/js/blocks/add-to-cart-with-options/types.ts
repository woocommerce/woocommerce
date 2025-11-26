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

// What are these normalized types for? Is not the same as ProductData and VariationData?
export type NormalizedProductData = ProductData & {
	id: number;
	min: number;
	max: number;
	step: number;
};

export type NormalizedVariationData = VariationData & {
	id: number;
	min: number;
	max: number;
	step: number;
	type: 'variation';
};
