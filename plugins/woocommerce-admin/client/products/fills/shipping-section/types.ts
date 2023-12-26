/**
 * External dependencies
 */
import { PartialProduct } from '@woocommerce/data';
import { HighlightSides } from '@woocommerce/product-editor';

export type ProductShippingSectionPropsType = {
	product?: PartialProduct;
};

export type DimensionPropsType = {
	onBlur: () => void;
	sanitize: ( value: PartialProduct[ keyof PartialProduct ] ) => string;
	suffix: unknown;
};

export type ShippingDimensionsPropsType = {
	dimensionProps: DimensionPropsType;
	setHighlightSide: ( side: HighlightSides ) => void;
};
