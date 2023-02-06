/**
 * External dependencies
 */
import { PartialProduct } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { ShippingDimensionsImageProps } from '../../fields/shipping-dimensions-image';

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
	setHighlightSide: (
		side: ShippingDimensionsImageProps[ 'highlight' ]
	) => void;
};
